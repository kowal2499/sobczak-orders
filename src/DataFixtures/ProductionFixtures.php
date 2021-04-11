<?php

namespace App\DataFixtures;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Production;
use App\Entity\TagAssignment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;


class ProductionFixtures extends BaseFixture implements DependentFixtureInterface
{

    protected function loadData(ObjectManager $manager)
    {
        for($i=0; $i < $this->agreementsNumber; $i++) {

            if ($this->faker->numberBetween(1,3) == 1) {
                continue;
            }

            $agreement = $this->getReference(Agreement::class . '_' . '' . '_' . $i);
            $createDate = $this->faker->dateTimeBetween($agreement->getCreateDate()->format('Y-m-d H:i:s'));

            foreach ($agreement->getAgreementLines() as $agreementLine) {

                foreach ([
                             ['slug' => 'dpt01', 'name' => 'Klejenie'],
                             ['slug' => 'dpt02', 'name' => 'CNC'],
                             ['slug' => 'dpt03', 'name' => 'Szlifowanie'],
                             ['slug' => 'dpt04', 'name' => 'Lakierowanie'],
                             ['slug' => 'dpt05', 'name' => 'Pakowanie']] as $stage) {
                    $production = new Production();
                    $production
                        ->setAgreementLine($agreementLine)
                        ->setStatus(0)
                        ->setCreatedAt($createDate)
                        ->setUpdatedAt($createDate)
                        ->setDepartmentSlug($stage['slug'])
                        ->setTitle($stage['name']);

                    $manager->persist($production);

                    if ($this->faker->numberBetween(0, 9) === 5) {
                        /** @var User $user */
                        $user = $this->getReference(self::REF_USER);

                        $tagAssignment = new TagAssignment();
                        $tagAssignment->setContextId($agreementLine);
                        $tagAssignment->setTagDefinition($this->getReference(self::REF_TAG_PRODUCTION_DOCUMENTATION));
                        $tagAssignment->setUser($user);
                        $manager->persist($tagAssignment);
                    }

                }

                // custom tasks

                if ($this->faker->boolean) {
                    $production = new Production();
                    $production
                        ->setAgreementLine($agreementLine)
                        ->setStatus(10)
                        ->setCreatedAt($createDate)
                        ->setUpdatedAt($createDate)
                        ->setDepartmentSlug('custom_task')
                        ->setTitle($this->faker->randomElement(['rurki fi20 stal', 'pręty', 'prowadnice GTV', 'deska świerkowa', 'dzwig do tafli szklanych', 'deska warstwowa dąb']));

                    $manager->persist($production);
                }
            }
        }
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            AgreementFixtures::class,
            TagFixtures::class,
            UserFixtures::class
        ];
    }
}
