<?php
/**
 * @author Artem Brovko <artem.brovko@modera.net>
 * @copyright 2019 Modera Foundation
 */

namespace App\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use FOS\UserBundle\Model\UserManagerInterface;

class AdminController extends EasyAdminController
{
    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * AdminController constructor.
     *
     * @param $userManager
     */
    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }


    public function createNewUserEntity()
    {
        return $this->userManager->createUser();
    }

    public function updateUserEntity($user)
    {
        $this->userManager->updateUser($user, false);
        parent::persistEntity($user);
    }
}