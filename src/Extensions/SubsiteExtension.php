<?php

use SilverStripe\Security\Member;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\LiteralField;


class SubsiteDeleteExtension extends DataExtension
{
    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        if(Member::currentUser() != null && Member::currentUser()->ID == 1)
        {
            $fields->addFieldToTab('Root.DELETE', LiteralField::create("Delete","<a class='btn action btn-primary' href='/SubsiteDeletion/DeleteSubsite?SubsiteID=".$this->owner->ID."'>Subsite Löschen</a>"));
            $fields->addFieldToTab('Root.MAKEPRIMARY', LiteralField::create("Make Primary","<a class='btn action btn-primary' href='/SubsiteDeletion/MakePrimary?SubsiteID=".$this->owner->ID."'>Hauptseite Löschen und Subsite umwandeln </a>"));
        }
    }
}