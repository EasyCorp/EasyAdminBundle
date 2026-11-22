<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class <?= $class_name; ?> extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // return $this->redirectToRoute('admin_user_index');

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render your own template (tip: extend from @EasyAdmin/page/dashboard.html.twig
        // to keep the widgets, or from @EasyAdmin/page/content.html.twig to start from an empty page)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    // Uncomment this method to display widgets in your dashboard instead of the welcome page
    // (add the 'use' statements of the widget classes of the EasyCorp\Bundle\EasyAdminBundle\Widget namespace)
    //
    // public function configureWidgets(): iterable
    // {
    //     yield StatWidget::new('Users')->setValue(1250)->setDelta(8.2);
    //     yield ChartWidget::new('Sign-ups')->setData(['Mon' => 12, 'Tue' => 19, 'Wed' => 7]);
    //     yield ListWidget::new('Latest orders')->setItems([
    //         ListItem::new('Order #1042')->setDescription('2 items')->setValue('$129.90'),
    //     ]);
    //
    //     // values can also be closures, which run only when the widget is displayed:
    //     // yield StatWidget::new('Products')->setValue(fn () => $this->productRepository->count([]));
    // }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<?= $site_title; ?>');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        // yield MenuItem::linkTo(SomeCrudController::class, 'The Label', 'fas fa-list');
    }
}
