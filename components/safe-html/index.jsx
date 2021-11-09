import DOMPurify from 'dompurify';
import PropTypes from 'prop-types';
import React from 'react';

const SafeHTML = ({
  className,
  html,
  tag: Tag,
}) => (
  <Tag
    className={className}
    dangerouslySetInnerHTML={{
      __html: DOMPurify.sanitize(html),
    }}
  />
);

SafeHTML.defaultProps = {
  className: '',
};

SafeHTML.propTypes = {
  className: PropTypes.string,
  html: PropTypes.string.isRequired,
  tag: PropTypes.string.isRequired,
};

export default SafeHTML;
