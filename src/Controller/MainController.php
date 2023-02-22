<?php

namespace App\Controller;

use App\Entity\News;
use App\Repository\NewsRepository;
use Carbon\Carbon;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
use DOMXPath;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/parse', name: 'app_main')]
    public function index(ManagerRegistry $myDoctrine): Response
    {
        // $url = 'https://highload.today';
        $url = 'https://books.toscrape.com';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
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

    #[Route('/', name: 'main_page')]
    public function main(ManagerRegistry $myDoctrine): Response
    {

        $News = $myDoctrine->getRepository(News::class)->findAll();

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'news' => $News
        ]);
    }

    #[Route('/news/{id}', name: 'single_news')]
    public function singleNews(News $news): Response
    {

        return $this->render('main/single.html.twig', [
            'news' => $news
        ]);
    }
}
