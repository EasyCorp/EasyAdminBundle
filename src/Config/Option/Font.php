<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config\Option;

/**
 * The fonts bundled with EasyAdmin, used in Theme::font(). They are served by
 * the backend itself, so they don't make any request to third-party services.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
enum Font: string
{
    // the fonts of the operating system of each user (system-ui, Segoe UI, etc.)
    case System = 'System';
    case Inter = 'Inter';
    case IbmPlexSans = 'IBM Plex Sans';
    case Rubik = 'Rubik';
    case AtkinsonHyperlegibleNext = 'Atkinson Hyperlegible Next';
    // the only monospace font of this list
    case JetBrainsMono = 'JetBrains Mono';
}
