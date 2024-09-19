<?php

namespace PubSubHelper\CommonTrait;

/**
 * Created by PhpStorm.
 * User: Nam Ngo
 * Date: 2019-10-28
 * Time: 17:52
 */
trait DoctrineTrait
{
    /**
     * Doctrine Entity Manager holder
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;


    /**
     * Get Doctrine Entity Manager
     *
     * @return \Doctrine\ORM\EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    public function getEM(): \Doctrine\ORM\EntityManager
    {
        // Fix the error "The EntityManager is closed."
        // when close the transaction
        if (!$this->_em->isOpen())
            $this->_em = $this->_em->create(
                $this->_em->getConnection(),
                $this->_em->getConfiguration()
            );

        return $this->_em;
    }

    public function setEM(\Doctrine\ORM\EntityManager $em)
    {
        $this->_em = $em;
    }

    /**
     * Helper Method
     * Gets the repository for an entity class.
     *
     * @param string $entityName
     * @param string $entityPrefix
     * @return \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     * @throws \Doctrine\ORM\ORMException
     */
    public function repo(string $entityName, string $entityPrefix = "\\Entity\\")
    {
        return $this->getEM()->getRepository($entityPrefix . $entityName);
    }
}