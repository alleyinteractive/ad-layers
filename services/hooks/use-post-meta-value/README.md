# Custom Hooks: usePostMetaValue

A custom React hook that wraps useEntityProp for working with a specific
postmeta value. It returns the value for the specified meta key as well as a
setter for the meta value. This hook is intended to reduce boilerplate code
in components that need to read and write postmeta. It differs from
usePostMeta in that it operates on a specific meta key/value pair.
By default, it operates on postmeta for the current post, but you can
optionally pass a post type and post ID in order to get and set post meta
for an arbitrary post.

## Usage

### Editing the Current Post's Meta

```jsx
const MyComponent = () => {
  const [myMetaKey, setMyMetaKey] = usePostMetaValue('my_meta_key');

  return (
    <TextControl
      label={__('My Meta Key', 'ad-layers')}
      onChange={setMyMetaKey}
      value={myMetaKey}
    />
  );
};
```

### Editing Another Post's Meta

```jsx
const MyComponent = ({
  postId,
  postType,
}) => {
  const [myMetaKey, setMyMetaKey] = usePostMetaValue('my_meta_key', postType, postId);

  return (
    <TextControl
      label={__('My Meta Key', 'ad-layers')}
      onChange={setMyMetaKey}
      value={myMetaKey}
    />
  );
};
```
