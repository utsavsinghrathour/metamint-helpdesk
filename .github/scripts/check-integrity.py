#!/usr/bin/env python3
"""
Repository & Config File Integrity Checker
Detects supply-chain malware and obfuscated payload injections in config files
(e.g., public/webpack.config.js, eslint.config.mjs, stylelint.config.mjs, etc.).
"""

import os
import re
import subprocess
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent.parent

# Known malicious payload markers (PolinRider / Contagious Interview campaign)
PAYLOAD_MARKERS = [
    "global.o",
    "i_tjmbnaan",
    "xaustunbrcrpkzrjlctnswcoeitoqohmfdgvy",
    "String.fromCharCode(127)",
    "String.fromCharCode",
    "7-nv1406",
    "7-nv1413",
    "7-nv1431",
    "7-v1613",
    "7-v2131",
    "7-v2702",
    "7-v2705",
    "7-v21442",
    "7-c1244",
    "7-c1250",
    "ethereum-rpc.publicnode.com",
    "eth.drpc.org",
    "rpc.mevblocker.io",
    "var _$_",
]

# Max expected file sizes for known config files (in bytes)
SIZE_LIMITS = {
    "public/webpack.config.js": 6144,  # ~6 KB
    "webpack.config.js": 6144,         # ~6 KB
    "eslint.config.mjs": 6144,         # ~6 KB
    "stylelint.config.mjs": 4096,      # ~4 KB
}
DEFAULT_CONFIG_SIZE_LIMIT = 16384      # ~16 KB

IGNORED_DIRS = {"node_modules", "vendor", ".git", "build", "dist", ".cache"}


def print_error(msg: str, file_path: Path | None = None, line_num: int | None = None):
    """Output GitHub Actions error annotation and console message."""
    if file_path:
        try:
            rel = file_path.relative_to(REPO_ROOT)
        except ValueError:
            rel = file_path
        if line_num is not None:
            print(f"::error file={rel},line={line_num}::{msg}")
            print(f"[FAIL] {rel}:{line_num}: {msg}")
        else:
            print(f"::error file={rel}::{msg}")
            print(f"[FAIL] {rel}: {msg}")
    else:
        print(f"::error::{msg}")
        print(f"[FAIL] {msg}")


def scan_for_payload_markers() -> int:
    """Scan tracked files using git grep for known malicious markers."""
    failures = 0
    print(">> Scanning tracked files for known payload markers...")

    for marker in PAYLOAD_MARKERS:
        cmd = [
            "git",
            "grep",
            "-nF",
            "--",
            marker,
            "--",
            ".",
            ":(exclude).github/workflows/integrity-check.yml",
            ":(exclude).github/scripts/check-integrity.py",
        ]
        try:
            res = subprocess.run(
                cmd,
                cwd=str(REPO_ROOT),
                capture_output=True,
                text=True,
            )
            if res.returncode == 0 and res.stdout.strip():
                print_error(
                    f"Injected payload marker '{marker}' found in tracked files:\n{res.stdout.strip()}"
                )
                failures += 1
        except FileNotFoundError:
            print("[WARN] git not available in PATH, skipping git grep scan")
            break

    if failures == 0:
        print("   [OK] No injected payload markers found in tracked files.")
    return failures


def find_config_files() -> list[Path]:
    """Find all configuration and build files across the repository."""
    config_files = set()

    # Explicit primary configs
    explicit = [
        "public/webpack.config.js",
        "eslint.config.mjs",
        "stylelint.config.mjs",
    ]
    for exp in explicit:
        p = REPO_ROOT / exp
        if p.exists():
            config_files.add(p)

    # Patterns for any config files in repo (excluding vendor, node_modules, .git)
    config_pattern = re.compile(
        r"(^|\.)(config|rc|webpack|vite|postcss|babel|tailwind)\.(m?js|cjs|ts|json)$|\.config\.mjs$",
        re.IGNORECASE,
    )

    for root, dirs, files in os.walk(REPO_ROOT):
        dirs[:] = [d for d in dirs if d not in IGNORED_DIRS]
        for f in files:
            if config_pattern.search(f):
                config_files.add(Path(root) / f)

    return sorted(list(config_files))


def check_config_structural_integrity(file_path: Path) -> list[str]:
    """Perform structural and sanity checks on a configuration file."""
    problems = []

    if not file_path.exists():
        return [f"Required config file {file_path.name} is missing"]

    try:
        content = file_path.read_text(encoding="utf-8", errors="replace")
    except Exception as e:
        return [f"Cannot read file: {e}"]

    # 1. Size check
    try:
        rel_key = str(file_path.relative_to(REPO_ROOT)).replace("\\", "/")
    except ValueError:
        rel_key = file_path.name

    limit = SIZE_LIMITS.get(rel_key, SIZE_LIMITS.get(file_path.name, DEFAULT_CONFIG_SIZE_LIMIT))
    if len(content) > limit:
        problems.append(
            f"File size {len(content)} bytes exceeds limit of {limit} bytes (possible payload injection)"
        )

    # 2. Check for whitespace padding (50+ consecutive spaces/tabs used to hide code off-screen)
    lines = content.splitlines()
    for idx, line in enumerate(lines, start=1):
        if re.search(r"[ \t]{50,}", line):
            problems.append(
                f"Line {idx} has extreme whitespace padding (50+ consecutive spaces) used to hide malicious code off-screen"
            )

        # In human-authored config files, single lines rarely exceed 500 characters
        if len(line) > 500:
            problems.append(
                f"Line {idx} exceeds 500 characters ({len(line)} chars), typical of minified payload injection"
            )

    # 3. Check for malware signatures in config file
    if re.search(r"\bglobal\s*(\.o|\[)", content):
        problems.append("Contains malicious 'global.o' / 'global[...]' payload loader")
    if re.search(r"\bvar\s+_\$_|\b_\$_[0-9a-f]{4}\b", content):
        problems.append("Contains obfuscated '_$_' variable array loader")
    if len(re.findall(r"\\x[0-9a-fA-F]{2}", content)) > 3:
        problems.append("Contains suspicious hex-encoded escape sequences")

    # 4. Check for dynamic code execution
    if re.search(r"\beval\s*\(", content):
        problems.append("Contains eval() call")
    if re.search(r"\bnew\s+Function\s*\(|[^.\w]Function\s*\(", content):
        problems.append("Contains Function() dynamic code constructor")
    if re.search(r"\bString\s*\.\s*fromCharCode\s*\(|\bfromCharCode\s*\(", content):
        problems.append("Contains String.fromCharCode() character decoding")
    if re.search(r"\b(atob|unescape)\s*\(", content):
        problems.append("Contains atob() or unescape() obfuscation decoding")

    # 5. Check for createRequire in ESM configs
    if file_path.name.endswith(".mjs") or file_path.name in ("eslint.config.mjs", "stylelint.config.mjs"):
        if "createRequire" in content:
            problems.append(
                "Contains createRequire (classic PolinRider injection vector in ESM config)"
            )

    # 6. Specific termination checks
    stripped = content.rstrip()
    if file_path.name.endswith("webpack.config.js") or file_path.name.endswith("stylelint.config.mjs"):
        if not stripped.endswith("};"):
            problems.append(
                f"{file_path.name} does not end cleanly with '}};' (unexpected trailing code appended)"
            )

    if file_path.name.endswith("eslint.config.mjs"):
        if not stripped.endswith("]);"):
            problems.append(
                f"{file_path.name} does not end cleanly with ']);' (unexpected trailing code appended)"
            )

    return problems


def main() -> int:
    print("=" * 65)
    print(" [INTEGRITY CHECK] Scanning repo & config files for malicious code")
    print(f" Target Root: {REPO_ROOT}")
    print("=" * 65)

    total_failures = 0

    # Step 1: Scan all tracked files for known malware markers
    marker_failures = scan_for_payload_markers()
    total_failures += marker_failures

    # Step 2: Discover and check configuration files
    config_files = find_config_files()
    print(f"\n>> Performing structural & integrity checks on {len(config_files)} config files...")

    for cfg in config_files:
        problems = check_config_structural_integrity(cfg)
        try:
            rel = cfg.relative_to(REPO_ROOT)
        except ValueError:
            rel = cfg

        if problems:
            for p in problems:
                print_error(p, cfg)
            total_failures += len(problems)
        else:
            print(f"   [OK] {rel} ({cfg.stat().st_size} bytes)")

    print("=" * 65)
    if total_failures > 0:
        print(f" [RESULT] FAILED: {total_failures} security/integrity issue(s) detected!")
        print("=" * 65)
        return 1
    else:
        print(f" [RESULT] PASSED: All {len(config_files)} config files and tracked files are clean.")
        print("=" * 65)
        return 0


if __name__ == "__main__":
    sys.exit(main())
