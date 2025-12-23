<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Admin email')
            ->addArgument('password', InputArgument::REQUIRED, 'Admin password')
            ->addArgument('name', InputArgument::OPTIONAL, 'Admin name', 'Admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $name = $input->getArgument('name');

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error("Un utilisateur avec l'email {$email} existe déjà.");
            return Command::FAILURE;
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setRoles(['ROLE_ADMIN']);

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Persister
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success("Utilisateur admin créé avec succès !");
        $io->table(
            ['Email', 'Name', 'Roles'],
            [[$user->getEmail(), $user->getName(), implode(', ', $user->getRoles())]]
        );

        return Command::SUCCESS;
    }
}
