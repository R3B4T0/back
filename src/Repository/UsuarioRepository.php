<?php

namespace App\Repository;

use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method Usuario|null find($id, $lockMode = null, $lockVersion = null)
 * @method Usuario|null findOneBy(array $criteria, array $orderBy = null)
 * @method Usuario[]    findAll()
 * @method Usuario[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsuarioRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $manager)
    {
        parent::__construct($registry, Usuario::class);
        $this->manager = $manager;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof Usuario) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function guardarUsuario($nombre, $apellidos, $email, $password, $foto, $role, $telefono)
    {
        $usuario = new Usuario();

        $usuario
            ->setNombre($nombre)
            ->setApellidos($apellidos)
            ->setEmail($email)
            ->setPassword($password)
            ->setFoto($foto)
            ->setTelefono($telefono)
            ->setRoles($role);

        $this->manager->persist($usuario);
        $this->manager->flush();
    }

    public function actualizaUsuario(Usuario $usuario): Usuario
    {
        $this->manager->persist($usuario);
        $this->manager->flush();
        
        return $usuario;
    }

    public function removeUsuario(Usuario $usuario)
    {
        $this->manager->remove($usuario);
        $this->manager->flush();
    }

    public function findBandas($role)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"' . $role . '"%');
        
        return $qb->getQuery()->getResult();
    }

    public function findMusicos($role)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"' . $role . '"%');
        
        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return Usuario[] Returns an array of Usuario objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Usuario
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
