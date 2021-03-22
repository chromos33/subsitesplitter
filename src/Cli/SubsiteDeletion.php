<?php

use SilverStripe\ORM\DB;
use SilverStripe\Dev\Debug;
use SilverStripe\Core\Convert;
use SilverStripe\Security\Member;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Subsites\Model\Subsite;
use TractorCow\Fluent\State\FluentState;
use SilverStripe\Subsites\Model\SubsiteDomain;



class SubsiteDeletion extends Controller
{
    private static $allowed_actions = [
        "DeleteSubsite" =>  true,
        "MakePrimary"   =>  true
    ];

    public function MakePrimary()
    {
        if($this->canDelete())
        {
            if(array_key_exists("SubsiteID",$_GET) && $_GET["SubsiteID"] != "")
            {
                ini_set('max_execution_time', 7200);
                $SubsiteID = Convert::raw2sql($_GET["SubsiteID"]);
                $IDArray = $this->GetSubSiteSiteTreeIDs(0);
                $this->DeleteSubsitePagesByChunk($IDArray,0);
                $this->MakeSubsitePrimary($SubsiteID);
                $this->DeleteSubsiteObject($SubsiteID);
                echo "Fertig";
            }
        }
    }

    private function MakeSubsitePrimary($SubsiteID)
    {
        $IDArray = $this->GetSubSiteSiteTreeIDs($SubsiteID);
        $IDChunkArray = array_chunk($IDArray,25);
        
        foreach($IDChunkArray as $IDChunk)
        {
            Versioned::set_stage(Versioned::DRAFT);
            $SubsitePages = SiteTree::get()->filter(["ID" => $IDChunk, "SubsiteID" => $SubsiteID]);
            foreach($SubsitePages as $SubsitePage)
            {
                $SubsitePage->SubsiteID = 0;
                $SubsitePage->write();
            }
            Versioned::set_stage(Versioned::LIVE);
            $SubsitePages = SiteTree::get()->filter(["ID" => $IDChunk, "SubsiteID" => $SubsiteID]);
            foreach($SubsitePages as $SubsitePage)
            {
                $SubsitePage->SubsiteID = 0;
                $SubsitePage->write();
                $SubsitePage->publishSingle();
            }
        }
    }


    public function DeleteSubsite()
    {
        if($this->canDelete())
        {
            if(array_key_exists("SubsiteID",$_GET) && $_GET["SubsiteID"] != "")
            {
                ini_set('max_execution_time', 7200);
                $SubsiteID = Convert::raw2sql($_GET["SubsiteID"]);
                $IDArray = $this->GetSubSiteSiteTreeIDs($SubsiteID);
                $this->DeleteSubsitePagesByChunk($IDArray,$SubsiteID);
                $this->DeleteSubsiteObject($SubsiteID);
                echo "Fertig";
            }
        }
    }
    private function DeleteSubsiteObject($SubsiteID)
    {
        $subsite = Subsite::get()->byID($SubsiteID);
        $subsitedomains = SubsiteDomain::get()->filter("SubsiteID",$SubsiteID);
        foreach($subsitedomains as $domain)
        {
            $domain->delete();
        }
        $subsite->delete();
    }
    private function DeleteSubsitePagesByChunk($IDArray,$SubsiteID)
    {
        foreach (TractorCow\Fluent\Model\Locale::getLocales() as $Locale) {
            FluentState::singleton()->withState(function (FluentState $state) use ($Locale,$IDArray,$SubsiteID) {
                $state->setLocale($Locale->Locale);
                $IDChunkArray = array_chunk($IDArray,25);
                Versioned::set_stage(Versioned::LIVE);
                foreach($IDChunkArray as $IDChunk)
                {
                    $SubsitePages = SiteTree::get()->filter(["ID" => $IDChunk, "SubsiteID" => $SubsiteID]);
                    foreach($SubsitePages as $SubsitePage)
                    {
                        $SubsitePage->doArchive();
                    }
                }
                Versioned::set_stage(Versioned::DRAFT);
                foreach($IDChunkArray as $IDChunk)
                {
                    $SubsitePages = SiteTree::get()->filter(["ID" => $IDChunk, "SubsiteID" => $SubsiteID]);
                    foreach($SubsitePages as $SubsitePage)
                    {
                        $SubsitePage->doArchive();
                    }
                }

            });
        }
    }
    private function GetSubSiteSiteTreeIDs($SubsiteID)
    {
        $IDs = DB::query("SELECT ID FROM SiteTree WHERE SubsiteID = $SubsiteID");
        $IDArray = [];
        foreach($IDs as $ID)
        {
            $IDArray[] = $ID["ID"];
        }
        return $IDArray;
    }
    // Only the Admin with ID 1 can delete Subsites or delete Primary and make subsite primary
    public function canDelete()
    {
        return Member::currentUser() != null && Member::currentUser()->ID == 1 && Permission::check('ADMIN');
    }
}