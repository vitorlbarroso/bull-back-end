<?php

namespace App\Enums;

enum OfferChargeTypeEnum:string
{
    case Monthly = "Monthly";
    case SemiAnnual = "SemiAnnual";
    case Annual = "Annual";
    case Personalized = "Personalized";
}
