<?php

namespace App\Enumeration;

/**
 * Enumeration representing different types of resources.
 * Each case is associated with a unique integer value.
 */
enum ResourceTypeMapping: int
{
    case LABOR = 1;
    case EQUIPMENT_RENTAL = 2;
    case RAW_MATERIALS = 3;
    case OFFICE_SUPPLIES = 4;
    case SOFTWARE_LICENSE = 5;
    case TRANSPORTATION = 6;
    case UTILITIES = 7;
    case MISCELLANEOUS = 8;
}
