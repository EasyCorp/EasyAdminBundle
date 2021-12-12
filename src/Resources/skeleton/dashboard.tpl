<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class <?= $class_name; ?> extends AbstractDashboardController
{
<?php if ($use_php_attributes): ?>
    #[Route('/admin', name: 'admin')]
<?php else: ?>
    /**
     * @Route("/admin", name="admin")
     */
<?php endif; ?>
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<?= $site_title; ?>');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
    }
}
