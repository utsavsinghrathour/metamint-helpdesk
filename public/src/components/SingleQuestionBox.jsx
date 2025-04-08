import React from 'react';
import { LuMessagesSquare } from 'react-icons/lu';
import '../styles/SingleQuestionBox.css';
const SingleQuestionBox = () => {
  return (
    <div className='rd-px-18 rd-flex rd-flex-col rd-gap-20 rd-mt-20 rd-mb-15'>
      <div className="rd-flex rd-gap-15 rd-border-light single-question-box-field rd-border-radius-md">
        <div className="flex-shrink-0 rd-text-blue">
          <LuMessagesSquare className="w-6 h-6 text-gray-500" />
        </div>
        <div>
          <h4 className="rd-title-text">How can we assist you today?</h4>
          <p className="rd-body-text">
            Feel free to ask any questions or share your concerns, and we'll get back to you as soon as possible.
          </p>
        </div>
      </div>
      <div className="rd-flex rd-gap-15 rd-border-light single-question-box-field rd-border-radius-md">
        <div className="flex-shrink-0 rd-text-blue">
          <LuMessagesSquare className="w-6 h-6 text-gray-500" />
        </div>
        <div>
          <h4 className="rd-title-text">How can we assist you today?</h4>
          <p className="rd-body-text">
            Feel free to ask any questions or share your concerns, and we'll get back to you as soon as possible.
          </p>
        </div>
      </div>
    </div>
  );
};
export default SingleQuestionBox;
