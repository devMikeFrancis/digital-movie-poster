/*
 * Spreading a line of text to the width of its box.
 *
 * "Span the width of the screen" made the plate span and left the words where
 * they were, so for a plain or neon plate the option did nothing you could see:
 * a 1228px box with 262px of text centred in it. Widening the tracking spans
 * the words themselves, and unlike growing the type it leaves the line height
 * alone, so turning it on does not shove the artwork around.
 */

// Far enough that the second measurement is clearly different, small enough
// that a long line does not have to reflow into something absurd to be read.
const PROBE_PX = 10;

/**
 * The letter-spacing that makes one line of text exactly fill a width.
 *
 * Width is linear in letter-spacing - every character gets the same addition -
 * so two measurements describe the whole line and the answer falls straight
 * out of them. Creeping up on it in a loop would reflow a dozen times per
 * resize to land on the same number.
 *
 * @param {(spacing: number) => number} measure width in px at a given spacing
 * @param {number} available width to fill, in px
 * @param {number} base the spacing the stylesheet already asks for, in px
 * @returns {number} spacing in px, never tighter than base
 */
export function solveSpacing(measure, available, base = 0) {
    const natural = measure(base);
    const probed = measure(base + PROBE_PX);
    const perPixel = (probed - natural) / PROBE_PX;

    // Nothing to spread, or nothing measured - a hidden element measures zero,
    // and dividing by that would hand back Infinity.
    if (!(perPixel > 0) || !(natural > 0) || !(available > 0)) {
        return base;
    }

    const spacing = base + (available - natural) / perPixel;

    // A name already too long for the screen is left alone rather than being
    // squeezed tighter than the design asks for.
    return spacing > base ? spacing : base;
}

/**
 * The width a plate has to offer its text: its own, less whatever padding the
 * decoration takes for itself.
 */
export function availableWidth(box, view = window) {
    const style = view.getComputedStyle(box);

    return (
        box.clientWidth -
        (parseFloat(style.paddingLeft) || 0) -
        (parseFloat(style.paddingRight) || 0)
    );
}

/**
 * Measure and apply, against real elements.
 *
 * Two details that are not obvious from the arithmetic:
 *
 * The text is measured at max-content, so that the box it is about to fill
 * cannot clamp the reading - a probe wide enough to wrap would report no gain
 * for the extra spacing, and the line would stay where it was.
 *
 * letter-spacing lands after the last character as well, so a line spread to
 * the full width sits one gap left of where it should. The trailing gap is
 * taken back with a negative margin rather than balanced with a matching
 * indent: an indent pays for the gap twice, and the words end up inset from
 * both edges of the very box they were asked to span.
 *
 * @returns {number} the spacing applied, in px
 */
export function spreadToWidth(text, box, view = window) {
    const inline = text.style;
    const width = inline.width;
    const maxWidth = inline.maxWidth;

    inline.width = 'max-content';
    inline.maxWidth = 'none';
    inline.whiteSpace = 'nowrap';
    // The stylesheet indents by one gap to re-centre the line at its natural
    // width; the negative margin below does that job here instead.
    inline.textIndent = '0px';

    const base = parseFloat(view.getComputedStyle(text).letterSpacing) || 0;

    const spacing = solveSpacing(
        (value) => {
            inline.letterSpacing = value + 'px';
            inline.marginRight = -value + 'px';

            return text.getBoundingClientRect().width - value;
        },
        availableWidth(box, view),
        base,
    );

    inline.width = width;
    inline.maxWidth = maxWidth;
    inline.letterSpacing = spacing + 'px';
    inline.marginRight = -spacing + 'px';

    return spacing;
}

/**
 * Clear a spread, so a plate that stops spanning goes back to the stylesheet's
 * own tracking rather than keeping the last width it was measured at.
 */
export function clearSpread(text) {
    text.style.letterSpacing = '';
    text.style.marginRight = '';
    text.style.whiteSpace = '';
    text.style.textIndent = '';
}
