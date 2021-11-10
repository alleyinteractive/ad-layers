import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { cloneDeep } from 'lodash';

/**
 * A custom React hook that wraps useEntityProp for working with postmeta. This
 * hook is intended to reduce boilerplate code in components that need to read
 * and write postmeta. By default, it operates on postmeta for the current post,
 * but you can optionally pass a post type and post ID in order to get and set
 * post meta for an arbitrary post.
 * @param {string} postType - Optional. The post type to get and set meta for.
 *                            Defaults to the post type of the current post.
 * @param {number} postId - Optional. The post ID to get and set meta for.
 *                          Defaults to the ID of the current post.
 * @returns {array} An array containing an object representing postmeta and an update function.
 */
const usePostMeta = (postType = null, postId = null) => {
  // Ensures that we have a post type, since we need it as an argument to useEntityProp.
  const type = useSelect((select) => postType || select('core/editor').getCurrentPostType(), []);

  // Get the return value from useEntityProp so we can wrap it for safety.
  const [metaRaw, setMetaRaw] = useEntityProp('postType', type, 'meta', postId);

  /*
   * Ensure meta is an object and set meta is a function. useEntityProp can
   * return `undefined` if the post type doesn't have support for custom-fields.
   */
  const meta = typeof metaRaw === 'object' ? metaRaw : {};
  const setMeta = typeof setMetaRaw === 'function'
    ? setMetaRaw
    : () => console.error(`Error attempting to set post meta for post type ${type}. Does it have support for custom-fields?`); // eslint-disable-line no-console

  /**
   * Define a wrapper for the setMeta function that performs a recursive clone
   * of the meta object to ensure that there are no issues related to updating
   * objects or array values within meta keys not triggering React or
   * Gutenberg's state management system realizing that there is a change due
   * to the fact that sub-items are stored as object references. These bugs are
   * extremely difficult to find and correct, so it makes sense to include this
   * functionality here as a catch-all on updates.
   * @param {object} next - The new value for meta.
   */
  const setMetaSafe = (next) => setMeta(cloneDeep(next));

  return [meta, setMetaSafe];
};

export default usePostMeta;
