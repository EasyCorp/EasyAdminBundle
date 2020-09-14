<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ImagineTwigExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('imagine_filter_safe', [$this, 'applyImagineFilter'], ['needs_environment' => true])
        ];
    }
    
    public function applyImagineFilter(Environment $environment, string $resource, ...$args)
    {
        $filter = $environment->getFilter('imagine_pattern');
        if (!$filter) {
            return $resource;
        }
        return $filter->getCallable()($resource, ...$args);
    }
}
