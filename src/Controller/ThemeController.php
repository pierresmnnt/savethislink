<?php

namespace App\Controller;

use App\Entity\Theme;
use App\Entity\Website;
use App\Form\ThemeType;
use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/themes')]
class ThemeController extends AbstractController
{
    #[Route('/', name: 'theme_index', methods: ['GET'])]
    public function index(ThemeRepository $themeRepository): Response
    {
        return $this->render('theme/index.html.twig', [
            'menu' => 'explore',
            'themes' => $themeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'theme_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $theme = new Theme();
        $form = $this->createForm(ThemeType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($theme);
            $entityManager->flush();

            return $this->redirectToRoute('theme_index');
        }

        return $this->render('theme/new.html.twig', [
            'theme' => $theme,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{slug}', name: 'theme_show', methods: ['GET'])]
    public function show(Theme $theme): Response
    {
        return $this->render('theme/show.html.twig', [
            'theme' => $theme,
        ]);
    }

    #[Route('/{slug}/new', name: 'theme_additem', methods: ['GET'])]
    public function addItem(Theme $theme): Response
    {
        return $this->redirectToRoute('website_new', ['slug' => $theme->getSlug()]);
    }

    #[Route('/{id}/edit', name: 'theme_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Theme $theme): Response
    {
        $this->denyAccessUnlessGranted('THEME_EDIT', $theme);

        $form = $this->createForm(ThemeType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('theme_index');
        }

        return $this->render('theme/edit.html.twig', [
            'theme' => $theme,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'theme_delete', methods: ['POST'])]
    public function delete(Request $request, Theme $theme): Response
    {
        $this->denyAccessUnlessGranted('THEME_DELETE', $theme);

        if ($this->isCsrfTokenValid('delete'.$theme->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($theme);
            $entityManager->flush();
        }

        return $this->redirectToRoute('theme_index');
    }
}
