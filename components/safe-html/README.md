# SafeHTML

A lightweight, reusable component for safely creating a component
that contains arbitrary HTML, such as from a REST API.

Uses `dangerouslySetInnerHTML` and `DOMPurify` under the hood.

Because of how `dangerouslySetInnerHTML` works, you need to
provide a tag to inject the HTML into. If the HTML you want to
inject is wrapped in the tag you want to use, you will need to
strip that out first.

## Usage

```jsx
<SafeHTML
  className="some-classname"
  html="<p>some arbitrary html</p>"
  tag="div"
/>
```

## Props

| prop      | required | type   |                                                 |
|-----------|----------|--------|-------------------------------------------------|
| className | No       | string | A classname for the element.                    |
| html      | Yes      | string | The arbitrary HTML to sanitize and inse.rt      |
| tag       | Yes      | string | The tag name to wrap the HTML in, e.g.,  `div`. |
