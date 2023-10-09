((style) => {
  const newStyle = document.createElement('link')
  newStyle.href = style.href
  newStyle.rel = 'preload'
  newStyle.as = 'style'
  style.replaceWith(newStyle)
  newStyle.onload = () => {
    newStyle.rel = 'stylesheet'
  }
})(document.currentScript.previousElementSibling)