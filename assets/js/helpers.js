export function toggleVisibilityClasses(element, removeVisibility) {
    if (removeVisibility) {
        element.classList.remove('d-block')
        element.classList.add('d-none')
    } else {
        element.classList.remove('d-none')
        element.classList.add('d-block')
    }
}