# Custom Hooks: usePostMeta

A custom React hook that creates and returns a new debounced version of the passed value that will postpone its execution until after wait milliseconds have elapsed since the last time it was invoked.

## Usage

```jsx
const MyComponent = () => {
  const [value, setValue] = useState('');
  const debouncedValue = useDebounce(value, 500);

  // This value will display after 500 miliseconds
  useEffect(() => {
    // Kickoff Function on a delay.
  }, [debouncedValue]);

  return (
    <>
      <TextControl
        label={__('Set Value', 'ad-layers')}
        onChange={(next) => setValue(next)}
        value={value}
      />
    </>
  );
};
```
