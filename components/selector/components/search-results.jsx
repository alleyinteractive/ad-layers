// Dependencies.
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Render search results list.
 */
const SearchResults = ({
  emptyLabel,
  error,
  id,
  isOpen,
  labelledbyId,
  loading,
  onSelect,
  options,
  selectedItems,
  threshold,
  value,
}) => {
  // Don't show anything if we aren't loading and don't have a value.
  if (!loading && (value === '' || threshold > value.length)) {
    return null;
  }

  let className = '';
  let content = '';

  if (loading) {
    className = 'loading';
    content = __('Loading...', 'ad-layers');
  } else if (error) {
    className = 'error';
    content = error;
  } else if (!loading && options.length === 0) {
    className = 'no-posts';
    content = emptyLabel;
  }

  // If we're loading
  // Or if we're not loading and have a search string with no posts.
  // Or if we're not loading and have an error.
  if (loading || (!loading && ((value && options.length === 0) || error))) {
    return (
      <div
        aria-busy
        className={
          classNames(
            'autocomplete__dropdown',
            {
              'autocomplete__dropdown--is-open': isOpen,
            },
          )
        }
      >
        <div
          className={
            classNames(
              'autocomplete__dropdown--notice',
              `autocomplete__${className}`,
            )
          }
        >
          {content}
        </div>
      </div>
    );
  }

  return (
    <div
      className={
        classNames(
          'autocomplete__dropdown',
          {
            'autocomplete__dropdown--is-open': isOpen,
          },
        )
      }
    >
      <ul
        role="listbox"
        aria-labelledby={labelledbyId}
        id={id}
        className={
          classNames(
            'autocomplete__dropdown--results',
            'autocomplete__list',
          )
        }
      >
        {options.map((item) => (
          <li
            className="autocomplete__list--item"
            key={item.id}
          >
            <Button
              onClick={() => onSelect(item)}
              type="button"
              disabled={selectedItems.some((post) => post.id === item.id)}
              isTertiary
            >
              {item.title}
            </Button>
          </li>
        ))}
      </ul>
    </div>
  );
};

/**
 * Set PropTypes for this component.
 * @type {object}
 */
SearchResults.propTypes = {
  emptyLabel: PropTypes.string.isRequired,
  error: PropTypes.string.isRequired,
  id: PropTypes.string.isRequired,
  isOpen: PropTypes.bool.isRequired,
  labelledbyId: PropTypes.string.isRequired,
  loading: PropTypes.bool.isRequired,
  options: PropTypes.arrayOf(
    PropTypes.shape({
      label: PropTypes.string,
      value: PropTypes.string,
    }),
  ).isRequired,
  onSelect: PropTypes.func.isRequired,
  selectedItems: PropTypes.shape([]).isRequired,
  threshold: PropTypes.number.isRequired,
  value: PropTypes.string.isRequired,
};

export default SearchResults;
