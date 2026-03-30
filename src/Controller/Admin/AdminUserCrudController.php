<?php

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdminUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminUser::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Admin User')
            ->setEntityLabelInPlural('Admin Users');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email');
        yield TextField::new('password')
            ->setFormTypeOption('attr', ['type' => 'password'])
            ->onlyOnForms()
            ->setHelp('Enter a new password (will be hashed automatically)');
        yield ArrayField::new('roles');
    }
}
