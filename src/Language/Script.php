<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Language;

enum Script: string
{
    case Latin = 'Latin';
    case Cyrillic = 'Cyrillic';
    case Arabic = 'Arabic';
    case Hebrew = 'Hebrew';
    case Devanagari = 'Devanagari';
    case Bengali = 'Bengali';
    case Greek = 'Greek';
    case Armenian = 'Armenian';
    case Georgian = 'Georgian';
    case Thai = 'Thai';
    case Tamil = 'Tamil';
    case Telugu = 'Telugu';
    case Kannada = 'Kannada';
    case Malayalam = 'Malayalam';
    case Gujarati = 'Gujarati';
    case Gurmukhi = 'Gurmukhi';
    case Odia = 'Odia';
    case Ethiopic = 'Ethiopic';
    case Coptic = 'Coptic';
    case CJK = 'CJK';
    case Other = 'Other';
}
