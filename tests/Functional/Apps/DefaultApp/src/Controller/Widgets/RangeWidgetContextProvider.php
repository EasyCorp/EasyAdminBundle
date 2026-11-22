<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetContextProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class RangeWidgetContextProvider implements WidgetContextProviderInterface
{
    public function getAttributes(Request $request): array
    {
        return ['range' => $request->query->getString('range', '7d')];
    }
}
