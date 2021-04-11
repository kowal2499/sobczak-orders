<?php

namespace App\DataFixtures;

use App\Entity\TagDefinition;
use Doctrine\Persistence\ObjectManager;

class TagFixtures extends BaseFixture
{
    protected function loadData(ObjectManager $manager)
    {
        $tagDoc = new TagDefinition(
            'Dokumentacja produkcyjna',
            'production',
            'fileEarmarkText',
            '#3e44ea',
            false
        );
        $manager->persist($tagDoc);

        $tagBug = new TagDefinition(
            'Reklamacja',
            'production',
            'bug',
            '#f40606',
            false
        );
        $manager->persist($tagDoc);
        $manager->persist($tagBug);

        $this->addReference(self::REF_TAG_PRODUCTION_DOCUMENTATION, $tagDoc);
        $this->addReference(self::REF_TAG_BUG, $tagDoc);
        $manager->flush();
    }
}