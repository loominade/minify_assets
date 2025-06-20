((style) => {
  const newStyle = document.createElement('link')
  newStyle.href = style.href
  newStyle.rel = 'preload'
  newStyle.as = 'style'
  if (style.media) {
    newStyle.media = style.media
  }
  style.replaceWith(newStyle)
  newStyle.onload = () => {
    newStyle.rel = 'stylesheet'
  }
})(document.currentScript.previousElementSibling)
