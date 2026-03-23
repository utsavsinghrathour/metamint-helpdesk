"use strict";
(globalThis["webpackChunkrobodesk"] = globalThis["webpackChunkrobodesk"] || []).push([["src_components_SingleTicket_jsx"],{

/***/ "./src/components/SingleTicket.jsx":
/*!*****************************************!*\
  !*** ./src/components/SingleTicket.jsx ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_icons_fa__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! react-icons/fa */ "./node_modules/react-icons/fa/index.mjs");
/* harmony import */ var react_icons_fa6__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! react-icons/fa6 */ "./node_modules/react-icons/fa6/index.mjs");
/* harmony import */ var react_icons_pi__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! react-icons/pi */ "./node_modules/react-icons/pi/index.mjs");
/* harmony import */ var react_icons_fi__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! react-icons/fi */ "./node_modules/react-icons/fi/index.mjs");
/* harmony import */ var react_icons_io5__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! react-icons/io5 */ "./node_modules/react-icons/io5/index.mjs");
/* harmony import */ var emoji_picker_react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! emoji-picker-react */ "./node_modules/emoji-picker-react/dist/emoji-picker-react.esm.js");
/* harmony import */ var _images_robodesk_logo_png__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../images/robodesk-logo.png */ "./src/images/robodesk-logo.png");
/* harmony import */ var _styles_MessageTabs_css__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../styles/MessageTabs.css */ "./src/styles/MessageTabs.css");
/* harmony import */ var _utils_modules__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/modules */ "./src/utils/modules.js");
/* harmony import */ var _utils_AppDataContext__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/AppDataContext */ "./src/utils/AppDataContext.jsx");
/* harmony import */ var _images_default_avatar_jpg__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../images/default-avatar.jpg */ "./src/images/default-avatar.jpg");
/* harmony import */ var _Hooks_sseManager__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../Hooks/sseManager */ "./src/Hooks/sseManager.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__);
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { if (r) i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n;else { var o = function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); }; o("next", 0), o("throw", 1), o("return", 2); } }, _regeneratorDefine2(e, r, n, t); }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }














// Skeleton Loader Component for Conversations

var ConversationSkeleton = function ConversationSkeleton(_ref) {
  var _ref$count = _ref.count,
    count = _ref$count === void 0 ? 6 : _ref$count;
  var skeletonMessages = Array.from({
    length: count
  }, function (_, i) {
    // Create more realistic message patterns
    var patterns = [{
      sender: 'other',
      lines: 2,
      width: '60%'
    }, {
      sender: 'me',
      lines: 1,
      width: '45%'
    }, {
      sender: 'me',
      lines: 2,
      width: '55%'
    }, {
      sender: 'other',
      lines: 3,
      width: '65%'
    }, {
      sender: 'other',
      lines: 1,
      width: '40%'
    }, {
      sender: 'me',
      lines: 2,
      width: '50%'
    }];
    return patterns[i % patterns.length];
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
    className: "rd-skeleton-container",
    style: {
      width: '100%'
    },
    children: [skeletonMessages.map(function (msg, index) {
      var prevMsg = index > 0 ? skeletonMessages[index - 1] : null;
      var isSameSender = prevMsg && prevMsg.sender === msg.sender;
      var marginBottom = isSameSender ? '4px' : '12px';
      return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
        className: "chat-message rd-flex rd-items-start ".concat(msg.sender === 'me' ? 'rd-justify-end' : 'rd-justify-start rd-gap-8'),
        style: {
          maxWidth: '100%',
          marginBottom: marginBottom,
          marginTop: index === 0 || !isSameSender ? '12px' : '4px',
          width: '100%',
          display: 'flex'
        },
        children: [msg.sender === 'other' && (!isSameSender || index === 0) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
          className: "rd-skeleton-avatar ",
          style: {
            width: '30px',
            height: '30px',
            borderRadius: '50px',
            backgroundColor: '#e5e7eb',
            flexShrink: 0,
            animation: 'shimmer 2s infinite'
          }
        }), msg.sender === 'other' && isSameSender && index !== 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
          style: {
            width: '30px',
            height: '30px'
          }
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
          className: "message-content rd-skeleton-bubble",
          style: {
            backgroundColor: msg.sender === 'me' ? '#007BFF' : '#FFFFFF',
            border: msg.sender === 'other' ? '1px solid #E0E0E0' : 'none',
            borderRadius: '10px',
            padding: '8px 12px',
            width: msg.width,
            position: 'relative',
            overflow: 'hidden',
            maxWidth: '70%'
          },
          children: [Array.from({
            length: msg.lines
          }, function (_, lineIndex) {
            return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
              className: "rd-skeleton-line",
              style: {
                height: '14px',
                backgroundColor: msg.sender === 'me' ? 'rgba(255, 255, 255, 0.3)' : '#e5e7eb',
                borderRadius: '4px',
                marginBottom: lineIndex < msg.lines - 1 ? '6px' : '0',
                width: lineIndex === msg.lines - 1 ? '60%' : '100%'
              }
            }, lineIndex);
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
            className: "rd-skeleton-time",
            style: {
              height: '10px',
              width: '35px',
              backgroundColor: msg.sender === 'me' ? 'rgba(255, 255, 255, 0.2)' : '#d1d5db',
              borderRadius: '3px',
              marginTop: '8px',
              marginLeft: msg.sender === 'me' ? 'auto' : '0'
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
            style: {
              position: 'absolute',
              top: 0,
              left: '-100%',
              width: '100%',
              height: '100%',
              background: msg.sender === 'me' ? 'linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent)' : 'linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent)',
              animation: 'shimmerMove 2s infinite'
            }
          })]
        })]
      }, index);
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("style", {
      jsx: "true",
      children: "\n        @keyframes shimmer {\n          0%,\n          100% {\n            opacity: 1;\n          }\n          50% {\n            opacity: 0.7;\n          }\n        }\n\n        @keyframes shimmerMove {\n          0% {\n            transform: translateX(0);\n          }\n          100% {\n            transform: translateX(200%);\n          }\n        }\n\n        .rd-skeleton-container {\n          display: flex;\n          flex-direction: column;\n          width: 100%;\n        }\n\n        .chat-message {\n          display: flex;\n          width: 100%;\n        }\n\n        .rd-justify-end {\n          justify-content: flex-end;\n        }\n\n        .rd-justify-start {\n          justify-content: flex-start;\n        }\n      "
    })]
  });
};
var TicketClosed = function TicketClosed(_ref2) {
  var handleFocusedTextArea = _ref2.handleFocusedTextArea,
    setActiveTab = _ref2.setActiveTab;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("h5", {
      className: "rd-fw-semi rd-mb-5 rd-body-text",
      children: "Still have questions?"
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("a", {
      href: "#",
      className: "rd-title-text",
      onClick: function onClick(e) {
        return e.preventDefault(), setActiveTab(0), handleFocusedTextArea(true);
      },
      children: "Start a new conversation"
    })]
  });
};

// Cache management utilities
var messageCache = new Map();
var CACHE_DURATION = 0;
var getCachedMessages = function getCachedMessages(ticketId) {
  var cached = messageCache.get(ticketId);
  if (cached && Date.now() - cached.timestamp < CACHE_DURATION) {
    return cached.messages;
  }
  return null;
};
var setCachedMessages = function setCachedMessages(ticketId, messages) {
  messageCache.set(ticketId, {
    messages: messages,
    timestamp: Date.now()
  });
};
var SingleTicket = function SingleTicket(_ref3) {
  var _window$robodeskData, _ticketsContext$3;
  var ticket = _ref3.ticket,
    onBack = _ref3.onBack,
    loggedInUserId = _ref3.loggedInUserId,
    handleFocusedTextArea = _ref3.handleFocusedTextArea,
    setActiveTab = _ref3.setActiveTab;
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(''),
    _useState2 = _slicedToArray(_useState, 2),
    message = _useState2[0],
    setMessage = _useState2[1];
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false),
    _useState4 = _slicedToArray(_useState3, 2),
    showPicker = _useState4[0],
    setShowPicker = _useState4[1];
  var _useState5 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null),
    _useState6 = _slicedToArray(_useState5, 2),
    previewImage = _useState6[0],
    setPreviewImage = _useState6[1];
  var _useState7 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(function () {
      // Initialize with cached messages if available
      return getCachedMessages(ticket.id) || [];
    }),
    _useState8 = _slicedToArray(_useState7, 2),
    chatMessages = _useState8[0],
    setChatMessages = _useState8[1];
  var _useState9 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(true),
    _useState0 = _slicedToArray(_useState9, 2),
    loading = _useState0[0],
    setLoading = _useState0[1];
  var _useState1 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null),
    _useState10 = _slicedToArray(_useState1, 2),
    error = _useState10[0],
    setError = _useState10[1];
  var _useState11 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(7),
    _useState12 = _slicedToArray(_useState11, 1),
    perPage = _useState12[0];
  var _useState13 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(null),
    _useState14 = _slicedToArray(_useState13, 2),
    offset = _useState14[0],
    setOffset = _useState14[1];
  var _useState15 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(0),
    _useState16 = _slicedToArray(_useState15, 2),
    totalComments = _useState16[0],
    setTotalComments = _useState16[1];
  var _useState17 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false),
    _useState18 = _slicedToArray(_useState17, 2),
    isFetching = _useState18[0],
    setIsFetching = _useState18[1];
  var _useState19 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false),
    _useState20 = _slicedToArray(_useState19, 2),
    initialLoadComplete = _useState20[0],
    setInitialLoadComplete = _useState20[1];
  var _useState21 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false),
    _useState22 = _slicedToArray(_useState21, 2),
    isPaginationLoading = _useState22[0],
    setIsPaginationLoading = _useState22[1];
  var textareaRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  var fileInputRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  var chatContainerRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  var image_urls = ((_window$robodeskData = window.robodeskData) === null || _window$robodeskData === void 0 ? void 0 : _window$robodeskData.imageUrls) || {};
  var previousScrollHeight = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(0);
  var beep = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(new Audio(wpAuth.notificationSound));
  var isAtTop = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  var hasConnected = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  var _useAppData = (0,_utils_AppDataContext__WEBPACK_IMPORTED_MODULE_5__.useAppData)(),
    loggedinContext = _useAppData.loggedinContext,
    loggedInUsrId = _useAppData.loggedInUsrId,
    ticketsContext = _useAppData.ticketsContext,
    updateTicketNotifications = _useAppData.updateTicketNotifications,
    isSingleTicketPage = _useAppData.isSingleTicketPage,
    setSingleTicketPage = _useAppData.setSingleTicketPage;

  //   console.log('A single ticket data', ticket);

  var _useState23 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false),
    _useState24 = _slicedToArray(_useState23, 2),
    ticketClosed = _useState24[0],
    setTicketClosed = _useState24[1];
  var _useState25 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
      imageUploading: false,
      imageUploadError: null,
      imageUploadMessage: null
    }),
    _useState26 = _slicedToArray(_useState25, 2),
    imageUpload = _useState26[0],
    setImageUpload = _useState26[1];
  var _useState27 = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(true),
    _useState28 = _slicedToArray(_useState27, 2),
    isMessagingActive = _useState28[0],
    setIsisMessagingActive = _useState28[1];
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(function () {
    setSingleTicketPage(true);
    return function () {
      setSingleTicketPage(false);
    };
  }, []);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(function () {
    if (ticket.notifications) {
      updateTicketNotifications(ticket.id, 'yes');
    }
  }, []);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(function () {
    beep.current.load();
    return function () {
      if (hasConnected.current) {
        hasConnected.current = false;
      }
    };
  }, []);
  var fetchComments = /*#__PURE__*/function () {
    var _ref4 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
      var fetchOffset,
        prepend,
        controller,
        timeoutId,
        response,
        data,
        formattedComments,
        newScrollHeight,
        scrollDiff,
        cached,
        _args = arguments,
        _t;
      return _regenerator().w(function (_context) {
        while (1) switch (_context.n) {
          case 0:
            fetchOffset = _args.length > 0 && _args[0] !== undefined ? _args[0] : 0;
            prepend = _args.length > 1 && _args[1] !== undefined ? _args[1] : false;
            if (!isFetching) {
              _context.n = 1;
              break;
            }
            return _context.a(2);
          case 1:
            setIsFetching(true);

            // Set pagination loading state for prepend operations
            if (prepend) {
              setIsPaginationLoading(true);
            }

            // Create an AbortController for timeout
            controller = new AbortController();
            timeoutId = setTimeout(function () {
              return controller.abort();
            }, 10000); // 10 second timeout
            _context.p = 2;
            // Only set loading for initial load, not for pagination
            if (!initialLoadComplete && !getCachedMessages(ticket.id)) {
              setLoading(true);
            }
            setError(null);
            _context.n = 3;
            return fetch("/wp-json/wp/v2/comments?post=".concat(ticket.id, "&per_page=").concat(perPage, "&offset=").concat(fetchOffset, "&order=asc&comment_type=ticket_replies&_fields=id,content,date,author_data,time_ago,author_name,author_image,comment_action"), {
              signal: controller.signal
            });
          case 3:
            response = _context.v;
            clearTimeout(timeoutId);
            if (response.ok) {
              _context.n = 4;
              break;
            }
            throw new Error("Failed to fetch comments: ".concat(response.statusText));
          case 4:
            _context.n = 5;
            return response.json();
          case 5:
            data = _context.v;
            formattedComments = formatTicketComments(data);
            setChatMessages(function (prev) {
              var newMessages = prepend ? [].concat(_toConsumableArray(formattedComments), _toConsumableArray(prev)) : formattedComments;
              var uniqueMessages = Array.from(new Map(newMessages.map(function (msg) {
                return [msg.id, msg];
              })).values());

              // Cache the messages
              if (!prepend) {
                setCachedMessages(ticket.id, uniqueMessages);
              }
              return uniqueMessages;
            });
            if (!initialLoadComplete) {
              setInitialLoadComplete(true);
              setLoading(false);

              // Scroll to bottom after initial load
              if (!prepend) {
                setTimeout(function () {
                  if (chatContainerRef.current) {
                    chatContainerRef.current.scrollTop = chatContainerRef.current.scrollHeight;
                  }
                }, 50);
              }
            }
            setIsFetching(false);
            setIsPaginationLoading(false);
            if (prepend && chatContainerRef.current) {
              // Maintain scroll position after prepending messages
              newScrollHeight = chatContainerRef.current.scrollHeight;
              scrollDiff = newScrollHeight - previousScrollHeight.current;
              chatContainerRef.current.scrollTop = scrollDiff;
            }
            _context.n = 7;
            break;
          case 6:
            _context.p = 6;
            _t = _context.v;
            clearTimeout(timeoutId);
            if (_t.name === 'AbortError') {
              // console.log('Request timed out, using cached data if available');
              // If we have cached data, use it
              cached = getCachedMessages(ticket.id);
              if (cached && cached.length > 0) {
                setChatMessages(cached);
                setError(null);
              } else {
                setError('Connection is slow. Messages may take time to load.');
              }
            } else {
              console.error('Failed to fetch comments:', _t);
              setError('Failed to load messages. Please try again.');
            }
            if (!initialLoadComplete) {
              setInitialLoadComplete(true);
              setLoading(false);
            }
            setIsFetching(false);
            setIsPaginationLoading(false);
          case 7:
            return _context.a(2);
        }
      }, _callee, null, [[2, 6]]);
    }));
    return function fetchComments() {
      return _ref4.apply(this, arguments);
    };
  }();
  var handleNewComment = function handleNewComment(commentData) {
    var play = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
    updateTicketNotifications(ticket.id, 'yes');
    setChatMessages(function (prev) {
      var _commentData$content;
      var messageExists = prev.some(function (msg) {
        return msg.id === commentData.id;
      });
      if (messageExists) {
        return prev;
      }
      if (play) {
        beep.current.play();
      }
      console.log('The author id is ', commentData.author_id);
      console.log('The actual authorid is', loggedInUserId);
      console.log(_typeof(commentData.author_id), _typeof(loggedInUserId));
      console.log(String(commentData.author_id) === String(loggedInUserId));
      var newMessage = {
        id: commentData.id,
        text: ((_commentData$content = commentData.content) === null || _commentData$content === void 0 ? void 0 : _commentData$content.rendered) || '',
        sender: String(commentData.author_id) === String(loggedInUserId) ? 'me' : 'other',
        senderName: commentData.author_name || 'Unknown',
        image: commentData.author_image || _images_default_avatar_jpg__WEBPACK_IMPORTED_MODULE_6__,
        created_at: commentData.created_at || new Date().toISOString(),
        isAdmin: commentData.author_id !== ticket.author_id
      };
      var uniqueMessages = Array.from(new Map([].concat(_toConsumableArray(prev), [newMessage]).map(function (msg) {
        return [msg.id, msg];
      })).values());

      // Update cache with new messages
      var sorted = uniqueMessages.sort(function (a, b) {
        return new Date(a.created_at) - new Date(b.created_at);
      });
      setCachedMessages(ticket.id, sorted);
      return sorted;
    });
  };
  var formatTicketComments = function formatTicketComments(comments) {
    var formattedComments = comments.map(function (comment) {
      var _comment$author_data, _comment$content, _comment$content2, _comment$author_data2;
      var authorId = (_comment$author_data = comment.author_data) === null || _comment$author_data === void 0 ? void 0 : _comment$author_data.id;
      var sender = String(authorId) === String(loggedInUserId) ? 'me' : 'other';
      return {
        id: comment.id,
        text: ((_comment$content = comment.content) === null || _comment$content === void 0 ? void 0 : _comment$content.rendered) || ((_comment$content2 = comment.content) === null || _comment$content2 === void 0 ? void 0 : _comment$content2.plain) || '',
        sender: sender,
        senderName: comment.author_name || 'Unknown',
        image: ((_comment$author_data2 = comment.author_data) === null || _comment$author_data2 === void 0 ? void 0 : _comment$author_data2.avatar) || comment.author_image || _images_default_avatar_jpg__WEBPACK_IMPORTED_MODULE_6__,
        created_at: comment.date,
        action: comment.comment_action,
        time_ago: comment.time_ago
      };
    });
    return formattedComments.sort(function (a, b) {
      return new Date(a.created_at) - new Date(b.created_at);
    });
  };

  // Initial fetch to get total comments and latest messages
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(function () {
    var fetchInitialComments = /*#__PURE__*/function () {
      var _ref5 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
        var cached, controller, timeoutId, response, total, initialOffset, _t2;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              // If we have cached messages, display them immediately
              cached = getCachedMessages(ticket.id);
              if (cached && cached.length > 0) {
                setChatMessages(cached);
                setLoading(false);
                setInitialLoadComplete(true);

                // Scroll to bottom after cached messages are set
                setTimeout(function () {
                  if (chatContainerRef.current) {
                    chatContainerRef.current.scrollTop = chatContainerRef.current.scrollHeight;
                  }
                }, 50);
              }
              _context2.p = 1;
              // Add timeout for the initial request too
              controller = new AbortController();
              timeoutId = setTimeout(function () {
                return controller.abort();
              }, 8000); // 8 second timeout
              _context2.n = 2;
              return fetch("/wp-json/wp/v2/comments?post=".concat(ticket.id, "&per_page=1&order=asc&_fields=id"), {
                signal: controller.signal
              });
            case 2:
              response = _context2.v;
              clearTimeout(timeoutId);
              if (response.ok) {
                _context2.n = 3;
                break;
              }
              throw new Error('Failed to fetch total comments');
            case 3:
              total = parseInt(response.headers.get('X-WP-Total'), 10) || 0;
              setTotalComments(total);

              // If there are no comments at all, just set empty state
              if (!(total === 0)) {
                _context2.n = 4;
                break;
              }
              setChatMessages([]);
              setLoading(false);
              setInitialLoadComplete(true);
              setError(null);
              return _context2.a(2);
            case 4:
              initialOffset = Math.max(total - perPage, 0);
              setOffset(initialOffset);

              // Fetch comments whether we have cache or not
              _context2.n = 5;
              return fetchComments(initialOffset, false);
            case 5:
              _context2.n = 7;
              break;
            case 6:
              _context2.p = 6;
              _t2 = _context2.v;
              if (_t2.name === 'AbortError') {
                if (!cached || cached.length === 0) {
                  setError('Connection is slow. Messages may take time to load.');
                }
              } else {
                console.error('Failed to fetch initial comments:', _t2);
                if (!cached || cached.length === 0) {
                  setError('Failed to load messages. Please try again.');
                }
              }
              setLoading(false);
              setInitialLoadComplete(true);
            case 7:
              return _context2.a(2);
          }
        }, _callee2, null, [[1, 6]]);
      }));
      return function fetchInitialComments() {
        return _ref5.apply(this, arguments);
      };
    }();
    fetchInitialComments();
  }, [ticket.id]);

  // Handle scrolling to load older messages

  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(function () {
    var textarea = textareaRef.current;
    if (textarea) {
      textarea.style.height = 'auto';
      textarea.style.height = "".concat(textarea.scrollHeight, "px");
    }
  }, [message]);

  // Scroll handling for loading older messages
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(function () {
    var chatContainer = chatContainerRef.current;
    if (!chatContainer) return;
    var handleScroll = function handleScroll() {
      if (chatContainer.scrollTop <= 50 && offset > 0 && !isFetching) {
        // Store current scroll height before fetching
        previousScrollHeight.current = chatContainer.scrollHeight;
        var newOffset = Math.max(offset - perPage, 0);
        setOffset(newOffset);
        fetchComments(newOffset, true);
      }
    };
    chatContainer.addEventListener('scroll', handleScroll);
    return function () {
      return chatContainer.removeEventListener('scroll', handleScroll);
    };
  }, [offset, isFetching]);

  // Scroll to bottom only for new messages, not pagination
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(function () {
    var chatContainer = chatContainerRef.current;
    // Only auto-scroll for initial load or new messages at bottom
    // Don't auto-scroll when loading older messages (pagination)
    if (chatContainer && !isFetching && !isPaginationLoading) {
      // Check if user is near bottom (within 100px)
      var isNearBottom = chatContainer.scrollHeight - chatContainer.scrollTop - chatContainer.clientHeight < 100;

      // Only auto-scroll if near bottom or it's the initial load
      if (isNearBottom || initialLoadComplete && chatMessages.length <= perPage) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
      }
    }
  }, [chatMessages.length]);
  var lastMessageId = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(function () {
    if (!chatMessages || chatMessages.length === 0) return false;
    return chatMessages[chatMessages.length - 1].id;
  }, [chatMessages]);
  var url = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(function () {
    if (!(ticket !== null && ticket !== void 0 && ticket.id)) return null;
    if (!loggedinContext && !loggedInUsrId) return null;
    if (!lastMessageId) return null;
    return "/wp-json/robodesk/v1/stream/comments/".concat(ticket.id, "/").concat(lastMessageId, "/").concat(loggedInUsrId);
  }, [lastMessageId, ticket.id]);
  var prevUrl = (0,react__WEBPACK_IMPORTED_MODULE_0__.useMemo)(function () {
    var _ticketsContext$, _ticketsContext$2;
    if (!((_ticketsContext$ = ticketsContext[0]) !== null && _ticketsContext$ !== void 0 && _ticketsContext$.id)) return null;
    if (!loggedinContext && !loggedInUsrId) return null;
    return "/wp-json/robodesk/v1/stream/tickets/0/".concat((_ticketsContext$2 = ticketsContext[0]) === null || _ticketsContext$2 === void 0 ? void 0 : _ticketsContext$2.id, "/").concat(loggedInUsrId);
  }, [(_ticketsContext$3 = ticketsContext[0]) === null || _ticketsContext$3 === void 0 ? void 0 : _ticketsContext$3.id, loggedInUsrId]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(function () {
    _Hooks_sseManager__WEBPACK_IMPORTED_MODULE_7__.sseManager.connect(url);
    var unsubscribeNewComment = _Hooks_sseManager__WEBPACK_IMPORTED_MODULE_7__.sseManager.on('new_comment', function (data) {
      var commentData = data;
      handleNewComment(commentData);
    });
    var unsubscribeTicketOpen = _Hooks_sseManager__WEBPACK_IMPORTED_MODULE_7__.sseManager.on('ticket_open', function (data) {
      setTicketClosed(false);
    });
    var unsubscribeTicketClosed = _Hooks_sseManager__WEBPACK_IMPORTED_MODULE_7__.sseManager.on('ticket_closed', function (data) {
      setTicketClosed(true);
    });
    var unsubscribeNewMineTicket = _Hooks_sseManager__WEBPACK_IMPORTED_MODULE_7__.sseManager.on('new_mine_comment', function (data) {
      handleNewComment(data, false);
    });
    return function () {
      unsubscribeNewComment();
      unsubscribeTicketOpen();
      unsubscribeTicketClosed();
      unsubscribeNewMineTicket();
      _Hooks_sseManager__WEBPACK_IMPORTED_MODULE_7__.sseManager.connect(prevUrl);
    };
  }, [url]);
  var handleSend = /*#__PURE__*/function () {
    var _ref6 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3() {
      var imageHTML, tempMessage, controller, timeoutId, _result$data$author_i, result, newMessage, _t3;
      return _regenerator().w(function (_context3) {
        while (1) switch (_context3.n) {
          case 0:
            if (!(message.trim() || previewImage)) {
              _context3.n = 5;
              break;
            }
            imageHTML = previewImage ? "<a href=\"".concat(previewImage, "\" target=\"_blank\"><img src=\"").concat(previewImage, "\" alt=\"Preview\" class=\"rd-comment-img\"  /></a>") : false;
            tempMessage = _objectSpread({
              id: "temp-".concat(Date.now()),
              text: message + (imageHTML ? '<br>' + imageHTML : ''),
              sender: 'me',
              senderName: 'Me',
              image: _images_default_avatar_jpg__WEBPACK_IMPORTED_MODULE_6__,
              created_at: new Date().toISOString(),
              isSending: true,
              isOptimistic: true
            }, previewImage && {
              imageUrl: previewImage
            }); // Optimistically add the message immediately
            setChatMessages(function (prev) {
              var updated = [].concat(_toConsumableArray(prev), [tempMessage]);
              setCachedMessages(ticket.id, updated);
              return updated;
            });
            setMessage('');
            setPreviewImage(null);

            // Send with timeout
            controller = new AbortController();
            timeoutId = setTimeout(function () {
              return controller.abort();
            }, 15000); // 15 second timeout for sending
            _context3.p = 1;
            _context3.n = 2;
            return (0,_utils_modules__WEBPACK_IMPORTED_MODULE_4__.sendReply)(ticket.id, loggedInUserId, message, previewImage, controller.signal);
          case 2:
            result = _context3.v;
            clearTimeout(timeoutId);
            if (!(!result.data || !result.data.content)) {
              _context3.n = 3;
              break;
            }
            throw new Error('Invalid response from server: missing content');
          case 3:
            newMessage = _objectSpread({
              id: result.data.id || tempMessage.id,
              text: result.data.content,
              sender: 'me',
              senderName: result.data.author_name || 'Me',
              image: (_result$data$author_i = result.data.author_image) !== null && _result$data$author_i !== void 0 ? _result$data$author_i : '/default-avatar.jpg',
              created_at: result.data.created_at || tempMessage.created_at,
              isAdmin: result.data.author_id ? result.data.author_id !== ticket.author_id : false
            }, previewImage && {
              imageUrl: previewImage
            }); // Replace temp message with real one
            setChatMessages(function (prev) {
              var updated = [].concat(_toConsumableArray(prev.filter(function (msg) {
                return msg.id !== tempMessage.id;
              })), [newMessage]).sort(function (a, b) {
                return new Date(a.created_at) - new Date(b.created_at);
              });
              // Update cache
              setCachedMessages(ticket.id, updated);
              return updated;
            });
            _context3.n = 5;
            break;
          case 4:
            _context3.p = 4;
            _t3 = _context3.v;
            clearTimeout(timeoutId);
            if (_t3.name === 'AbortError') {
              // Keep the optimistic message but mark it as pending
              setChatMessages(function (prev) {
                return prev.map(function (msg) {
                  return msg.id === tempMessage.id ? _objectSpread(_objectSpread({}, msg), {}, {
                    isPending: true,
                    isSending: false
                  }) : msg;
                });
              });
              setError('Message is taking longer to send. It will be delivered when connection improves.');
            } else {
              console.error('Error sending message:', _t3);
              // Remove the failed message
              setChatMessages(function (prev) {
                var updated = prev.filter(function (msg) {
                  return msg.id !== tempMessage.id;
                });
                setCachedMessages(ticket.id, updated);
                return updated;
              });
              setError('Failed to send message. Please try again.');
            }
          case 5:
            return _context3.a(2);
        }
      }, _callee3, null, [[1, 4]]);
    }));
    return function handleSend() {
      return _ref6.apply(this, arguments);
    };
  }();
  var handleEmojiClick = function handleEmojiClick(emojiObject) {
    setMessage(function (prev) {
      return prev + emojiObject.emoji;
    });
    setShowPicker(false);
  };
  var toggleEmojiPicker = function toggleEmojiPicker() {
    setShowPicker(function (prev) {
      return !prev;
    });
  };
  var handleFileUpload = function handleFileUpload(e) {
    var file = e.target.files[0];
    if (!file) return;
    var MAX_FILE_SIZE = 3145728;
    var ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    var ERROR_MESSAGES = {
      INVALID_TYPE: 'Only JPEG, PNG, or GIF images are allowed.',
      SIZE_EXCEEDED: 'Image size must be less than 3MB.'
    };

    // Helper function to update state
    var updateUploadState = function updateUploadState(state) {
      return setImageUpload(_objectSpread({
        imageUploading: false,
        imageUploadMessage: null,
        imageUploadError: null
      }, state));
    };

    // Reset input
    e.target.value = null;

    // Validate file type
    if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
      updateUploadState({
        imageUploadError: true,
        imageUploadMessage: ERROR_MESSAGES.INVALID_TYPE
      });
      alert(ERROR_MESSAGES.INVALID_TYPE);
      return;
    }

    // Validate file size
    if (file.size > MAX_FILE_SIZE) {
      updateUploadState({
        imageUploadError: true,
        imageUploadMessage: ERROR_MESSAGES.SIZE_EXCEEDED
      });
      alert(ERROR_MESSAGES.SIZE_EXCEEDED);
      return;
    }

    // Start upload process
    updateUploadState({
      imageUploading: true
    });
    var reader = new FileReader();
    reader.onload = function () {
      setPreviewImage(reader.result);
      updateUploadState({});
    };
    reader.onerror = function () {
      updateUploadState({
        imageUploadError: true,
        imageUploadMessage: 'Error reading the file.'
      });
      alert('Error reading the file.');
    };
    reader.readAsDataURL(file);
  };
  var triggerFileInput = function triggerFileInput() {
    return fileInputRef.current.click();
  };
  var formatTimestamp = function formatTimestamp(timestamp) {
    return new Date(timestamp).toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit'
    });
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
    className: "rd-single-ticket rd-flex rd-flex-col rd-h-full",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
      className: "single-ticket-header rd-flex-col rd-flex rd-align-center rd-justify-between rd-border-bottom-light rd-px-18 rd-py-15",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
        className: "rd-flex rd-align-center",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("button", {
          className: "icon-dark back-button rd-flex rd-items-center rd-bg-transparent rd-border-none rd-p-0 rd-m-0 rd-back-arrow-position",
          onClick: onBack,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(react_icons_fa__WEBPACK_IMPORTED_MODULE_9__.FaChevronLeft, {})
        }), image_urls.wide_logo && image_urls.wide_logo.trim() !== '' ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("img", {
          src: image_urls.wide_logo,
          alt: "Plugin Wide Logo",
          className: "rd-h-auto rd-header-logo",
          onError: function onError(e) {
            return e.target.style.display = 'none';
          }
        }) : image_urls.logo && image_urls.logo.trim() !== '' ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("img", {
          src: image_urls.logo,
          alt: "Plugin Logo",
          className: "rd-h-auto rd-header-logo",
          onError: function onError(e) {
            return e.target.style.display = 'none';
          }
        }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
          className: "rd-h-auto rd-header-logo default-logo",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("img", {
            src: _images_robodesk_logo_png__WEBPACK_IMPORTED_MODULE_2__,
            alt: "Default Plugin Logo",
            className: "rd-h-auto rd-header-logo"
          })
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
        className: "rd-body-text rd-mt-30",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("p", {
          className: "rd-text-center rd-body-text rd-mb-0",
          children: "If you are interested in working with us, take a step towards discovering exciting opportunities by reaching out to us. We look forward to talking with you soon!"
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
      ref: chatContainerRef,
      className: "chat-container rd-flex rd-flex-col rd-flex-grow rd-px-18 rd-py-15 rd-overflow-auto ".concat(ticketClosed ? 'ticket-closed' : ''),
      children: [isPaginationLoading && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
        className: "rd-pagination-loader rd-mb-10",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(ConversationSkeleton, {
          count: 3
        })
      }), error && chatMessages.length > 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
        className: "rd-connection-warning rd-px-12 rd-py-8 rd-mb-10 rd-text-center",
        style: {
          backgroundColor: '#fff3cd',
          border: '1px solid #ffc107',
          borderRadius: '8px',
          color: '#856404',
          fontSize: '12px'
        },
        children: error
      }), loading && !initialLoadComplete ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(ConversationSkeleton, {}) : error && chatMessages.length === 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("p", {
        className: "err_msg rd-text-center",
        children: error
      }) : chatMessages.length === 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("p", {
        className: "rd-text-center rd-body-text",
        children: "No conversations yet. Start a conversation below."
      }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
        children: chatMessages.map(function (msg, index) {
          var prevMsg = index > 0 ? chatMessages[index - 1] : null;
          var isSameSender = prevMsg && prevMsg.sender === msg.sender;
          var marginBottom = isSameSender ? '4px' : '12px';
          return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
            className: "chat-message rd-flex rd-items-start ".concat(msg.sender === 'me' || msg.isSending ? 'rd-ml-auto sent' : 'rd-flex rd-gap-8 received'),
            style: {
              maxWidth: '70%',
              marginBottom: marginBottom,
              marginTop: index === 0 || !isSameSender ? '12px' : '4px'
            },
            children: [(!isSameSender || index === 0) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("img", {
              src: msg.image,
              alt: msg.sender === 'me' ? 'You' : msg.senderName,
              className: "rd-rounded-full rd-object-cover",
              style: {
                height: '30px',
                width: '30px',
                borderRadius: '50px',
                aspectRatio: '1'
              }
            }), isSameSender && index !== 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
              style: {
                width: '30px',
                height: '30px'
              }
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
              className: "message-content",
              style: {
                backgroundColor: msg.sender === 'me' || msg.isSending ? '#007BFF' : '#FFFFFF',
                color: msg.sender === 'me' || msg.isSending ? '#FFFFFF' : '#000000',
                border: msg.sender === 'other' && !msg.isSending ? '1px solid #E0E0E0' : 'none',
                borderRadius: '10px',
                padding: '8px 12px',
                maxWidth: '100%'
              },
              children: [msg.isSending || msg.isPending ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("span", {
                className: "rd-body-text single-message",
                style: {
                  opacity: msg.isPending ? 0.7 : 1
                },
                dangerouslySetInnerHTML: {
                  __html: msg.text
                }
              }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
                children: msg.text && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("span", {
                  className: ['rd-body-text', 'single-message', msg.action ? 'rd-is-action' : null].filter(Boolean).join(' '),
                  dangerouslySetInnerHTML: {
                    __html: msg.text
                  }
                })
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
                className: "rd-text-small rd-text-gray",
                style: {
                  marginTop: '4px',
                  textAlign: msg.sender === 'me' || msg.isSending ? 'right' : 'left',
                  fontSize: '10px'
                },
                children: msg.isSending ? '' : msg.isPending ? 'Pending...' : formatTimestamp(msg.created_at)
              })]
            })]
          }, msg.id);
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
      className: "chat-input rd-flex rd-flex-col rd-px-18 rd-py-15 rd-border-top-light rd-bg-white  ".concat(previewImage ? 'has-preview' : ''),
      children: [previewImage && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
        className: "rd-mb-10 rd-flex rd-align-center",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("img", {
            src: previewImage,
            alt: "Preview",
            className: "rd-max-w-full rd-h-auto rd-border-radius-md rd-preview-image",
            style: {
              maxWidth: '80px'
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("button", {
            onClick: function onClick() {
              return setPreviewImage(null);
            },
            className: "rd-ml-10 rd-text-red-500 rd-border-none rd-cursor-pointer remove-preview rd-position-absolute rd-p-0",
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(react_icons_io5__WEBPACK_IMPORTED_MODULE_10__.IoClose, {})
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
        className: "rd-flex rd-align-center rd-justify-between",
        children: !ticketClosed ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("textarea", {
            id: "mesg",
            ref: textareaRef,
            value: message,
            onChange: function onChange(e) {
              return setMessage(e.target.value);
            },
            onKeyDown: function onKeyDown(e) {
              if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSend();
              }
            },
            placeholder: "Write a message...",
            className: "rd-flex-grow rd-border-none rd-outline-none rd-py-5 rd-w-full rd-resize-none",
            rows: "1"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
            className: "rd-flex rd-gap-20 rd-relative",
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("button", {
              onClick: toggleEmojiPicker,
              className: "rd-bg-transparent rd-border-none rd-mr-10 icon-dark rd-cursor-pointer rd-p-0",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(react_icons_fa6__WEBPACK_IMPORTED_MODULE_11__.FaRegFaceSmileBeam, {
                className: "icon-dark",
                size: 20
              })
            }), showPicker && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
              className: "rd-position-absolute rd-bottom-0 rd-right-0 rd-top-0 emoji-picker-wrap",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(emoji_picker_react__WEBPACK_IMPORTED_MODULE_1__["default"], {
                onEmojiClick: handleEmojiClick
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
              className: "rd-flex",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("input", {
                type: "file",
                ref: fileInputRef,
                onChange: handleFileUpload,
                accept: "image/*",
                className: "rd-hidden"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("button", {
                onClick: triggerFileInput,
                className: "rd-bg-transparent rd-border-none rd-mr-10 rd-cursor-pointer rd-p-0 icon-dark",
                children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(react_icons_fi__WEBPACK_IMPORTED_MODULE_12__.FiPaperclip, {
                  className: "icon-dark",
                  size: 20
                })
              })]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("button", {
              onClick: handleSend,
              className: "rd-bg-transparent rd-border-none rd-ml-10 icon-dark rd-cursor-pointer rd-p-0",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(react_icons_pi__WEBPACK_IMPORTED_MODULE_13__.PiPaperPlaneRightFill, {
                className: "icon-dark",
                size: 20
              })
            })]
          })]
        }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(TicketClosed, {
          handleFocusedTextArea: handleFocusedTextArea,
          setActiveTab: setActiveTab
        })
      })]
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SingleTicket);

/***/ }),

/***/ "./src/images/default-avatar.jpg":
/*!***************************************!*\
  !*** ./src/images/default-avatar.jpg ***!
  \***************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

module.exports = __webpack_require__.p + "images/default-avatar.3842d8da.jpg";

/***/ })

}]);
//# sourceMappingURL=src_components_SingleTicket_jsx.js.map?ver=880d818c1e0c4ce7ba74