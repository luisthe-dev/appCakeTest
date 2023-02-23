<?php

namespace App\Controller;

use App\Entity\News;
use App\Repository\NewsRepository;
use Carbon\Carbon;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
use DOMXPath;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{

    private ?string $baseUrl = 'https://books.toscrape.com';

    #[Route('/parse', name: 'app_main')]
    public function index(ManagerRegistry $myDoctrine): Response
    {
        // $url = 'https://highload.today';
        $url = 'https://books.toscrape.com';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);

        $data = curl_exec($ch);

        $dom = new DOMDocument();

        @$dom->loadHTML($data);

        $xDom = new DOMXPath($dom);

        $titles = $xDom->evaluate('//article[@class="product_pod"]//h3/a');
        $desc = $xDom->evaluate('//article[@class="product_pod"]//div/p[@class="price_color"]');
        $images = $xDom->evaluate('//article[@class="product_pod"]//a/img');
        $date_added = Carbon::now();

        // $titles = $xDom->evaluate('//div[@class="lenta-item"]/a/h2');

        $entityManager = $myDoctrine->getManager();

        for ($i = 0; $i < $titles->count(); $i++) {

            $News = $myDoctrine->getRepository(News::class)->findOneBy(['title' => $titles[$i]->textContent]);

            if (!$News) {
                $News = new News();
                $News->setTitle($titles[$i]->textContent);
                $News->setDescription($desc[$i]->textContent);
                $News->setImage($images[$i]->getAttribute('src'));
                $News->setDateAdded($date_added);
                $News->setDateUpdated($date_added);
            } else {
                $News->setDescription($desc[$i]->textContent);
                $News->setImage($images[$i]->getAttribute('src'));
                $News->setDateUpdated($date_added);
            }
            $entityManager->persist($News);
        }

        $entityManager->flush();

        return $this->json(['message' => 'News Scraped Successfully']);
    }

    #[Route('/news/parse', name: 'self_parse')]
    public function selfParse(ManagerRegistry $myDoctrine): Response
    {

        $this->index($myDoctrine);

        return $this->redirect('/news');
    }

    #[Route('/news', name: 'main_page')]
    public function main(ManagerRegistry $myDoctrine, Request $request): Response
    {

        $pageCount = 10;
        $previous = 1;
        $page = 1;
        $next = 1;

        if (!$request->query->get('page')) {
            $page = 1;
        } else {
            $page = $request->query->get('page');
        }

        $News = $myDoctrine->getRepository(News::class)->findAll();
        $NewsData = array();

        for ($i = (($page - 1) * $pageCount); $i < ($page * $pageCount) && $i < sizeof($News); $i++) {
            array_push($NewsData, $News[$i]);
        }

        if ($i < sizeof($News))  $next = $page + 1;

        if ($page != 1) $previous = $page - 1;

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'news' => $NewsData,
            'current' => $page,
            'next' => $next,
            'previous' => $previous
        ]);
    }

    #[Route('/news/{id}', name: 'single_news')]
    public function singleNews(News $news): Response
    {

        return $this->render('main/single.html.twig', [
            'news' => $news,
            'base_url' => $this->baseUrl
        ]);
    }

    #[Route('news/delete/{id}', name: 'delete_news')]
    public function deleteNews(ManagerRegistry $myDoctrine, $id): Response
    {


        $entityManager = $myDoctrine->getManager();

        $news = $myDoctrine->getRepository(News::class)->find($id);

        if ($news) {
            $entityManager->remove($news);
            $entityManager->flush();
        }

        return $this->redirect('/news');
    }
}
