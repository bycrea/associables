<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Assos;
use AppBundle\Entity\Donation;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * AssosRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AssosRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param $getCategory //Id de la catégorie
     * @return array
     *
     * Retourne les associations liées à une catégorie
     */
    public function findByCategory($getCategory)
    {
        $queryBuilder = $this->createQueryBuilder('assos');

        $queryBuilder
            ->leftJoin('assos.categories', 'category')
            ->where('category.id = :id')
            ->setParameter('id', $getCategory)
            ->orderBy('assos.name', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }


    /**
     * @param $search //caractères recherchés
     * @param $catg //tri par catégorie
     * @return array
     *
     * Retourne les associations contenant les caractères '$search'
     * En fonction, aussi, de la catégorie sélectionné
     */
    public function findBySearchBar($search, $catg)
    {
        $queryBuilder = $this->createQueryBuilder('assos');

        $queryBuilder
            ->where('assos.name LIKE :search')
            ->setParameter('search', '%' . $search . '%');

        if (null != $catg) {
            $queryBuilder
                ->leftJoin('assos.categories', 'catg')
                ->andWhere('catg.id = :catg')
                ->setParameter('catg', $catg);
        }

        $queryBuilder
            ->orderBy('assos.name', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }


    /**
     * @param $limit //max results
     * @return Paginator
     *
     * Retourne le nombre $limit d'associations triées du don le plus récent au moins récent.
     *
     * NB: avec une jointure OneToMany dans notre queryBuilder, setMaxResult n'a plus le même comportement
     * On utilise donc new Paginator pour palier au problème
     */
    public function findMostRecent($limit)
    {
        $queryBuilder = $this->createQueryBuilder('assos');

        $queryBuilder
            ->leftJoin('assos.donations', 'don')
            ->orderBy('don.createdAt', 'DESC')
            ->setMaxResults($limit);

        return new Paginator($queryBuilder);
    }


    /**
     * @param $user //utilisateur connecté
     * @return array
     *
     * Retourne les associtations auquelles un utilisateur à donné
     * paymentStatus = en attente de transfert OU transféré
     * Et le montant des dons pour chaque association
     */
    public function findUserAssosAndAmount($user)
    {
        $queryBuilder = $this->createQueryBuilder('a');

        $queryBuilder
            ->leftJoin('a.donations', 'don')
            ->addSelect('SUM(don.amount) AS amount')
            ->where('don.user = :user')
            ->setParameter('user', $user)
            ->andWhere('don.paymentStatus IN (:status)')
            ->setParameter('status', [Donation::PAY_IN_TRANSFER, Donation::PAY_PROCESSED])
            ->groupBy('a.id')
            ->orderBy('amount', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }


    /**
     * @return array
     *
     * Retourne l'entity Assos et le total dons en attente de transfert
     * Groupé par id Assos et trié du plus grand au plus petit total de dons
     * (amount DESC)
     *
     *
     * * Equivalent de la requête en SQL
     *
     * SELECT *, SUM(don.amount) AS amount
     * FROM assos AS a
     * LEFT JOIN donation AS don ON a.id = don.assos_id
     * WHERE don.payment_status IN (4)
     * GROUP BY a.id
     * ORDER BY amount DESC;
     */
    public function findAwaitingPayments()
    {
        $queryBuilder = $this->createQueryBuilder('a');

        $queryBuilder
            ->leftJoin('a.donations', 'don')
            ->addSelect('SUM(don.amount) AS amount')
            ->where('don.paymentStatus IN (:status)')
            ->setParameter('status', [Donation::PAY_IN_TRANSFER])
            ->groupBy('a.id')
            ->orderBy('amount', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }
}
