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
    
    public function applyImagineFilter(Environment $environment, ...$args)
    {
        $filter = $environment->getFilter('imagine_filter');
        if (!$filter) {
            return $args[0];
        }
        
        return $filter->getCallable()(...$args);
    }
}
