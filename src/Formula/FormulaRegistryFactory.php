<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

final class FormulaRegistryFactory
{
    public static function create(): FormulaRegistry
    {
        $registry = new FormulaRegistry();

        $registry->register(new AutomatedReadabilityIndex());
        $registry->register(new ColemanLiau());
        $registry->register(new Crawford());
        $registry->register(new DaleChall());
        $registry->register(new FernandezHuerta());
        $registry->register(new FleschKincaidGradeLevel());
        $registry->register(new FleschReadingEase());
        $registry->register(new FogPL());
        $registry->register(new Gulpease());
        $registry->register(new GunningFog());
        $registry->register(new GutierrezPolini());
        $registry->register(new Lix());
        $registry->register(new Osman());
        $registry->register(new SmogIndex());
        $registry->register(new Spache());
        $registry->register(new SzigrisztPazos());
        $registry->register(new WienerSachtextformel());

        return $registry;
    }
}
