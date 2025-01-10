<?php

namespace App\Enums;

enum MediaTypeEnum:string
{

    case Logo = "Logo";
    case Banner = "Banner";
    case Thumbnail = "Thumbnail";
    case Content = "Content";
    case Attachment = "Attachment";

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

}
