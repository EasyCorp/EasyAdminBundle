<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config\Option;

/**
 * The most common OpenType features of fonts, used in the 'features' argument of
 * Theme::font(). Each value is the item of the 'font-feature-settings' CSS
 * property that enables or disables the feature. Fonts only apply the features
 * included in their files.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
enum FontFeature: string
{
    // the zero digit has a slash, so it's easier to tell apart from the O letter
    case SlashedZero = '"zero" 1';
    // all digits have the same width, so numbers are aligned in columns
    case TabularNumbers = '"tnum" 1';
    // disables the ligatures that join some letters (e.g. 'fi' and 'fl')
    case NoLigatures = '"liga" 0';
    // disables the alternative glyphs that depend on the surrounding characters
    // (e.g. the arrow displayed instead of '->' in some fonts)
    case NoContextualAlternates = '"calt" 0';
}
