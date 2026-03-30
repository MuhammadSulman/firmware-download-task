<?php

namespace App\Controller\Admin;

use App\Entity\SoftwareVersion;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class SoftwareVersionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SoftwareVersion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Software Version')
            ->setEntityLabelInPlural('Software Versions')
            ->setDefaultSort(['name' => 'ASC', 'systemVersion' => 'ASC'])
            ->setSearchFields(['name', 'systemVersion', 'systemVersionAlt'])
            ->setPaginatorPageSize(50);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('name')->setChoices([
                'MMI Prime CIC' => 'MMI Prime CIC',
                'MMI Prime NBT' => 'MMI Prime NBT',
                'MMI Prime EVO' => 'MMI Prime EVO',
                'MMI PRO CIC' => 'MMI PRO CIC',
                'MMI PRO NBT' => 'MMI PRO NBT',
                'MMI PRO EVO' => 'MMI PRO EVO',
                'LCI MMI Prime CIC' => 'LCI MMI Prime CIC',
                'LCI MMI Prime NBT' => 'LCI MMI Prime NBT',
                'LCI MMI Prime EVO' => 'LCI MMI Prime EVO',
                'LCI MMI PRO CIC' => 'LCI MMI PRO CIC',
                'LCI MMI PRO NBT' => 'LCI MMI PRO NBT',
                'LCI MMI PRO EVO' => 'LCI MMI PRO EVO',
            ]))
            ->add('latest');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield ChoiceField::new('name', 'Product Name')
            ->setChoices([
                'MMI Prime CIC' => 'MMI Prime CIC',
                'MMI Prime NBT' => 'MMI Prime NBT',
                'MMI Prime EVO' => 'MMI Prime EVO',
                'MMI PRO CIC' => 'MMI PRO CIC',
                'MMI PRO NBT' => 'MMI PRO NBT',
                'MMI PRO EVO' => 'MMI PRO EVO',
                'LCI MMI Prime CIC' => 'LCI MMI Prime CIC',
                'LCI MMI Prime NBT' => 'LCI MMI Prime NBT',
                'LCI MMI Prime EVO' => 'LCI MMI Prime EVO',
                'LCI MMI PRO CIC' => 'LCI MMI PRO CIC',
                'LCI MMI PRO NBT' => 'LCI MMI PRO NBT',
                'LCI MMI PRO EVO' => 'LCI MMI PRO EVO',
            ])
            ->setHelp('Select the product line this firmware belongs to');

        yield TextField::new('systemVersion', 'System Version')
            ->setHelp('Full version string with "v" prefix, e.g. v3.3.7.mmipri.c');

        yield TextField::new('systemVersionAlt', 'System Version Alt')
            ->setHelp('Version string WITHOUT "v" prefix, e.g. 3.3.7.mmipri.c — this is what customers enter');

        yield UrlField::new('link', 'Download Folder Link')
            ->setHelp('Google Drive folder link (leave empty if not applicable)')
            ->hideOnIndex();

        yield UrlField::new('stLink', 'ST Download Link')
            ->setHelp('ST (Standard) firmware download link')
            ->hideOnIndex();

        yield UrlField::new('gdLink', 'GD Download Link')
            ->setHelp('GD firmware download link')
            ->hideOnIndex();

        yield BooleanField::new('latest', 'Latest Version?')
            ->setHelp('Mark as the latest version for this product line. Only ONE version per product line should be marked as latest.');
    }
}
