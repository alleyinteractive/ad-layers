// Dependencies.
import React, {
  useCallback,
  useEffect,
  useRef,
  useState,
} from 'react';
import PropTypes from 'prop-types';
import apiFetch from '@wordpress/api-fetch';
import classNames from 'classnames';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { v4 as uuidv4 } from 'uuid';

// Components.
import SearchResults from './components/search-results';

// Custom hooks.
import useDebounce from '@/services/hooks/use-debounce';

// Styles.
import './styles.scss';

/**
 * Render autocomplete component.
 */
const Selector = ({
  type,
  className,
  emptyLabel,
  label,
  maxPages,
  multiple,
  onSelect,
  placeholder,
  subTypes,
  selected,
  threshold,
}) => {
  // Unique ID.
  const uniqueKey = uuidv4();

  // Setup state.
  const [error, setError] = useState('');
  const [foundItems, setFoundItems] = useState([]);
  const [isOpen, setIsOpen] = useState(false);
  const [loading, setLoadState] = useState(false);
  const [searchString, setSearchString] = useState('');
  const [selectedItems, setSelectedItems] = useState([]);

  // Create ref.
  const ref = useRef();

  // Debounce search string from input.
  const debouncedSearchString = useDebounce(searchString, 750);

  /**
   * Make API request for items by search string.
   *
   * @param {int} page current page number.
   */
  const fetchItems = useCallback(async (page = 1) => {
    // Prevent fetch if we haven't
    // met our search string threshold.
    if (debouncedSearchString.length < threshold) {
      setFoundItems([]);
      return;
    }

    // Page count.
    let totalPages = 0;

    if (page === 1) {
      // Reset state before we start the fetch.
      setFoundItems([]);

      // Set the loading flag.
      setLoadState(true);
    }

    // Get search results from the API and store them.
    const path = addQueryArgs(
      '/wp/v2/search',
      {
        page,
        search: debouncedSearchString,
        subtype: subTypes.length > 0 ? subTypes.join(',') : 'any',
        type,
      },
    );

    // Fetch items by page.
    await apiFetch({ path, parse: false })
      .then((response) => {
        const totalPagesFromResponse = parseInt(
          response.headers.get('X-WP-TotalPages'),
          10,
        );
        // Set totalPage count to received page count unless larger than maxPages prop.
        totalPages = totalPagesFromResponse > maxPages
          ? maxPages : totalPagesFromResponse;
        return response.json();
      })
      .then((items) => {
        setFoundItems((prevState) => [...prevState, ...items]);
        setLoadState(false);

        // Continue to fetch additional page results.
        if (
          (totalPages && totalPages > page)
          || (page >= 1 && multiple && selectedItems.length > 0)
        ) {
          fetchItems(page + 1);
        }
      })
      .catch((err) => setError(err.message));
  }, [debouncedSearchString, type, maxPages, multiple, subTypes, selectedItems.length, threshold]);

  /**
   * On Mount, pre-fill selected buttons, if they exist.
   */
  useEffect(() => {
    setSelectedItems(selected);
  }, [selected]);

  /**
   * Handles submitting the input value on debounce.
   */
  useEffect(() => {
    if (debouncedSearchString && threshold <= debouncedSearchString.length) {
      fetchItems();
    } else { setFoundItems([]); }
  }, [debouncedSearchString, fetchItems, threshold]);

  /**
   * Mousedown event callback.
   *
   * @param {MouseEvent} event mouse event.
   */
  const handleClick = (event) => {
    setIsOpen(ref.current.contains(event.target));
  };

  /**
   * Keydown event callback.
   *
   * @param {KeyboardEvent} event keyboard event.
   */
  const handleKeyboard = (event) => {
    if (event.key === 'Escape') { setIsOpen(false); }
  };

  /**
   * Handle keydown.
   */
  useEffect(() => {
    document.addEventListener('keydown', handleKeyboard);
    return () => document.removeEventListener('keydown', handleKeyboard);
  });

  /**
   * Handles mouse down.
   */
  useEffect(() => {
    if (ref) {
      document.addEventListener('mousedown', handleClick);
    }
    return () => document.removeEventListener('mousedown', handleClick);
  });

  /**
   * Handle item selection from search results
   * and return value to parent.
   *
   * @param {object} item selected item object.
   */
  const handleItemSelection = (item) => {
    let newSelectedItems = [];

    // If multiple item selection is available.
    // Add selection to foundItems array.
    if (selectedItems.some((arrayItem) => arrayItem.id === item.id)) {
      const index = selectedItems.findIndex((arrayItem) => arrayItem.id === item.id);
      newSelectedItems = [
        ...selectedItems.slice(0, index),
        ...selectedItems.slice(index + 1, selectedItems.length),
      ];
    } else if (multiple) {
      newSelectedItems = [
        ...selectedItems,
        item,
      ];
    } else {
      // Set single item to state.
      newSelectedItems = [item];
      // Reset state and close dropdown.
      setIsOpen(false);
    }

    setSelectedItems(newSelectedItems);
    onSelect(newSelectedItems);
  };

  return (
    <form
      className="autocomplete__component"
      onSubmit={(event) => event.preventDefault()}
    >
      <div
        className={
          classNames(
            'components-base-control',
            'autocomplete-base-control',
            className,
          )
        }
        ref={ref}
      >
        <div
          aria-expanded={isOpen}
          aria-haspopup="listbox"
          aria-owns={`listbox-${uniqueKey}`}
          className={
            classNames(
              'components-base-control__field',
              'autocomplete-base-control__field',
            )
          }
          role="combobox" // eslint-disable-line jsx-a11y/role-has-required-aria-props
        >
          <label
            className={
              classNames(
                'components-base-control__label',
                'autocomplete-base-control__label',
              )
            }
            htmlFor={`autocomplete-${uniqueKey}`}
          >
            <div>{label}</div>
          </label>
          {selectedItems.length > 0 ? (
            <ul
              role="listbox"
              aria-labelledby={`autocomplete-${uniqueKey}`}
              id={`selected-items-${uniqueKey}`}
              className={
                classNames(
                  'autocomplete__selection--results',
                  'autocomplete__selection-list',
                )
              }
            >
              {selectedItems.map((item) => (
                <li
                  className="autocomplete__selection-list--item"
                  key={item.title}
                >
                  <Button
                    className="autocomplete__selection-list--item--button"
                    isSecondary
                    isSmall
                    onClick={() => handleItemSelection(item)}
                    type="button"
                  >
                    {item.title}
                  </Button>
                </li>
              ))}
            </ul>
          ) : null}
          <input
            aria-autocomplete="list"
            autoComplete="off"
            className={
              classNames(
                'components-text-control__input',
                'autocomplete-text-control__input',
                {
                  'autocomplete-text-control__input--working': isOpen,
                },
              )
            }
            id={`autocomplete-${uniqueKey}`}
            onChange={(e) => setSearchString(e.target.value)}
            onFocus={() => setIsOpen(true)}
            placeholder={placeholder}
            type="text"
            value={searchString}
          />
        </div>
        <SearchResults
          emptyLabel={emptyLabel}
          error={error}
          labelledById={`autocomplete-${uniqueKey}`}
          id={`listbox-${uniqueKey}`}
          isOpen={isOpen}
          loading={loading && debouncedSearchString}
          onSelect={handleItemSelection}
          options={foundItems}
          selectedItems={selectedItems}
          threshold={threshold}
          value={debouncedSearchString}
        />
      </div>
    </form>
  );
};

/**
 * Set initial props.
 * @type {object}
 */
Selector.defaultProps = {
  type: 'post',
  className: '',
  emptyLabel: __('No items found', 'ad-layers'),
  label: __('Search for items', 'ad-layers'),
  maxPages: 5,
  multiple: false,
  placeholder: __('Search for items', 'ad-layers'),
  subTypes: [],
  selected: [],
  threshold: 3,
};

/**
 * Set PropTypes for this component.
 * @type {object}
 */
Selector.propTypes = {
  type: PropTypes.string,
  className: PropTypes.string,
  emptyLabel: PropTypes.string,
  label: PropTypes.string,
  maxPages: PropTypes.number,
  multiple: PropTypes.bool,
  onSelect: PropTypes.func.isRequired,
  placeholder: PropTypes.string,
  subTypes: PropTypes.arrayOf(PropTypes.string),
  selected: PropTypes.arrayOf([
    PropTypes.shape({
      id: PropTypes.number,
      title: PropTypes.string,
    }),
  ]),
  threshold: PropTypes.number,
};

export default Selector;
