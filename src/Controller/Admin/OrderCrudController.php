<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setSearchFields(['reference', 'customer.email', 'paymentReference'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('Voir');
            });
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('reference', 'Référence');
        yield AssociationField::new('customer', 'Client')
            ->formatValue(function ($value, $entity) {
                return $entity->getCustomer()->getEmail();
            });
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Payé' => OrderStatus::PAID->value,
                'En traitement' => OrderStatus::PROCESSING->value,
                'Complété' => OrderStatus::COMPLETED->value,
                'Annulé' => OrderStatus::CANCELLED->value,
            ]);
        yield MoneyField::new('totalAmount', 'Montant total')
            ->setCurrency('EUR')
            ->hideOnForm();
        yield TextField::new('currency', 'Devise')->hideOnIndex();
        yield TextField::new('paymentProvider', 'Provider')->hideOnIndex();
        yield TextField::new('paymentReference', 'Référence paiement')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm();
        
        if ($pageName === Crud::PAGE_DETAIL) {
            yield CollectionField::new('items', 'Articles')
                ->setTemplatePath('admin/order_items.html.twig');
        }
    }
}
