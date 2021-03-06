<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditPasswordType;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[Route('/account')]
class AccountController extends AbstractController
{
    private EventDispatcherInterface $eventDispatcher;
    private TokenStorageInterface $tokenStorage;

    public function __construct(EventDispatcherInterface $eventDispatcher, TokenStorageInterface $tokenStorage)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/settings', name: 'account_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request): Response
    {      
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash("success", "Profil mis à jour");
            
            return $this->redirectToRoute('account_settings');
        }

        return $this->render('user/settings.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit-password',name: 'password_edit', methods: ['GET', 'POST'])]
    public function editPassword(Request $request, UserPasswordEncoderInterface $passwordEncoder) {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(EditPasswordType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordEncoder->encodePassword($user, $form->get('newPassword')->getData()));
            $this->getDoctrine()->getManager()->flush();

            $this->eventDispatcher->dispatch(new LogoutEvent($request, $this->tokenStorage->getToken()));
            $this->tokenStorage->setToken(null);
            
            $this->addFlash('success', 'Votre mot de passe a bien été modifié. Veuillez vous reconnecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/edit_password.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/delete/{id}', name: 'account_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($user !== $this->getUser()) {
            throw new AuthenticationException();
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->eventDispatcher->dispatch(new LogoutEvent($request, $this->tokenStorage->getToken()));
            $this->tokenStorage->setToken(null);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Compte supprimé');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/following', name: 'account_following', methods: ['GET'])]
    public function following(): Response
    {
        return $this->render('user/following.html.twig');
    }
}
