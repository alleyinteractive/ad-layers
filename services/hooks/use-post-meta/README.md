# Custom Hooks: usePostMeta

A custom React hook that wraps useEntityProp for working with postmeta. This
hook is intended to reduce boilerplate code in components that need to read
and write postmeta. By default, it operates on postmeta for the current post,
but you can optionally pass a post type and post ID in order to get and set
post meta for an arbitrary post.

## Usage

### Editing the Current Post's Meta

```jsx
const MyComponent = () => {
  const [meta, setMeta] = usePostMeta();
  const { my_meta_key: myMetaKey } = meta;

  return (
    <TextControl
      label={__('My Meta Key', 'ad-layers')}
      onChange={(next) => setMeta({ ...meta, my_meta_key: next })}
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
  const [meta, setMeta] = usePostMeta(postType, postId);
  const { my_meta_key: myMetaKey } = meta;

  return (
    <TextControl
      label={__('My Meta Key', 'ad-layers')}
      onChange={(next) => setMeta({ ...meta, my_meta_key: next })}
      value={myMetaKey}
    />
  );
};
```
