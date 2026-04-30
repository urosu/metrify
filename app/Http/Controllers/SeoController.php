<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * SeoController — serves the /seo page (Google Search Console performance).
 *
 * Reads: gsc_queries, gsc_pages, gsc_daily_stats, search_console_properties
 * Writes: nothing (read-only analytics page)
 * Called by: Route::get('/seo', SeoController::class) in routes/web.php
 *
 * The controller returns mock data matching the full page spec for v1 frontend
 * development. Real DB queries replace the mock arrays once the GSC sync pipeline
 * is live (see docs/planning/backend.md §SyncSearchConsoleJob).
 *
 * Source rule: only 'gsc' (emerald-500) is relevant on this page. All other
 * source badges are greyed with "Not applicable on /seo — SEO is pre-click".
 *
 * @see docs/pages/seo.md
 * @see docs/planning/backend.md#SearchConsoleSync
 */
class SeoController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'tab'      => ['sometimes', 'nullable', 'in:queries,pages,countries,devices'],
            'from'     => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to'       => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'sort'     => ['sometimes', 'nullable', 'in:clicks,impressions,ctr,position'],
            'sort_dir' => ['sometimes', 'nullable', 'in:asc,desc'],
            'q'        => ['sometimes', 'nullable', 'string', 'max:200'],
            'country'  => ['sometimes', 'nullable', 'string', 'max:10'],
            'device'   => ['sometimes', 'nullable', 'in:all,desktop,mobile,tablet'],
            'pos_min'  => ['sometimes', 'nullable', 'numeric', 'min:1'],
            'pos_max'  => ['sometimes', 'nullable', 'numeric', 'min:1'],
        ]);

        $tab     = $validated['tab']     ?? 'queries';
        $from    = $validated['from']    ?? now()->subDays(27)->toDateString();
        $to      = $validated['to']      ?? now()->toDateString();
        $sort    = $validated['sort']    ?? 'clicks';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $q       = $validated['q']       ?? null;

        return Inertia::render('Seo/Index', [
            'tab'       => $tab,
            'from'      => $from,
            'to'        => $to,
            'sort'      => $sort,
            'sort_dir'  => $sortDir,
            'filter_q'  => $q,
            'kpis'      => $this->mockKpis(),
            'trend'     => $this->mockTrend(),
            'queries'   => $this->mockQueries(),
            'pages'     => $this->mockPages(),
            'countries' => $this->mockCountries(),
            'devices'   => $this->mockDevices(),
            'movers_up'   => $this->mockMoversUp(),
            'movers_down' => $this->mockMoversDown(),
            'gsc_connected'  => true,
            'gsc_lag_warning' => true,
        ]);
    }

    // ── Mock data ────────────────────────────────────────────────────────────────
    // All mock data is realistic GSC data for a mid-size ecommerce store.
    // Replace with real DB queries once SyncSearchConsoleJob is live.

    /** @return array<int,array<string,mixed>> */
    private function mockKpis(): array
    {
        return [
            [
                'name'           => 'Clicks',
                'qualifier'      => '28d',
                'value'          => 14820,
                'delta_pct'      => 8.4,
                'source'         => 'gsc',
                'sparkline'      => [410,398,425,450,480,462,490,512,505,520,535,518,540,560,548,575,590,582,600,615,608,625,640,635,652,668,662,678,692,685],
                'lower_is_better'=> false,
                'unit'           => null,
            ],
            [
                'name'           => 'Impressions',
                'qualifier'      => '28d',
                'value'          => 412000,
                'delta_pct'      => 12.1,
                'source'         => 'gsc',
                'sparkline'      => [12800,12400,13200,13800,14200,13900,14600,15200,14900,15600,16200,15800,16500,17200,16800,17500,18200,17800,18500,19200,18800,19500,20200,19800,20500,21200,20800,21500,22200,21800],
                'lower_is_better'=> false,
                'unit'           => null,
            ],
            [
                'name'           => 'CTR',
                'qualifier'      => '28d',
                'value'          => 3.6,
                'delta_pct'      => -3.4,
                'source'         => 'gsc',
                'sparkline'      => null,
                'lower_is_better'=> false,
                'unit'           => 'pct',
            ],
            [
                'name'           => 'Avg Position',
                'qualifier'      => '28d',
                'value'          => 14.2,
                'delta_pct'      => -1.8,
                'source'         => 'gsc',
                'sparkline'      => [16.2,16.0,15.8,15.6,15.5,15.4,15.3,15.2,15.1,15.0,14.9,14.8,14.7,14.6,14.5,14.4,14.3,14.2,14.1,14.0,13.9,13.8,13.7,13.6,13.5,13.4,13.3,13.2,13.1,13.0],
                'lower_is_better'=> true,
                'unit'           => null,
            ],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function mockTrend(): array
    {
        $rows = [];
        $base = strtotime('2026-04-01');
        $clickBase = 420;
        $impBase = 12000;
        for ($i = 0; $i < 28; $i++) {
            $date = date('Y-m-d', $base + $i * 86400);
            $jitter = ($i % 7 === 0 || $i % 7 === 6) ? 0.7 : 1.0; // weekends lower
            $growth = 1 + ($i * 0.008);
            $clicks = (int) round($clickBase * $growth * $jitter + rand(-15, 20));
            $impressions = (int) round($impBase * $growth * $jitter + rand(-200, 300));
            $ctr = $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0;
            $position = round(16.2 - $i * 0.11 + ($jitter < 1 ? 0.5 : 0), 1);
            // Last 3 days are provisional (GSC lag)
            $isPartial = $i >= 25;
            $rows[] = [
                'date'        => $date,
                'clicks'      => $clicks,
                'impressions' => $impressions,
                'ctr'         => $ctr,
                'avg_position'=> $position,
                'is_partial'  => $isPartial,
            ];
        }
        return $rows;
    }

    /** @return array<int,array<string,mixed>> */
    private function mockQueries(): array
    {
        return [
            ['query'=>'best running shoes for flat feet','clicks'=>128,'impressions'=>4200,'ctr'=>3.05,'position'=>8.4,'position_trend'=>[12.1,11.8,10.3,9.8,9.1,8.9,8.4],'best_page'=>'/collections/running/flat-feet','is_brand'=>false,'opportunity'=>'striking_distance'],
            ['query'=>'nexstage shoes','clicks'=>342,'impressions'=>1840,'ctr'=>18.59,'position'=>1.8,'best_page'=>'/','position_trend'=>[2.1,2.0,1.9,1.9,1.8,1.8,1.8],'is_brand'=>true,'opportunity'=>null],
            ['query'=>'trail running shoes women','clicks'=>95,'impressions'=>3800,'ctr'=>2.5,'position'=>11.2,'position_trend'=>[14.2,13.8,13.1,12.6,12.0,11.6,11.2],'best_page'=>'/collections/trail-women','is_brand'=>false,'opportunity'=>'striking_distance'],
            ['query'=>'minimalist running shoes','clicks'=>87,'impressions'=>3100,'ctr'=>2.8,'position'=>9.8,'position_trend'=>[11.5,11.0,10.8,10.5,10.2,10.0,9.8],'best_page'=>'/collections/minimalist','is_brand'=>false,'opportunity'=>null],
            ['query'=>'shoes for overpronation','clicks'=>74,'impressions'=>2900,'ctr'=>2.55,'position'=>13.4,'position_trend'=>[16.2,15.8,15.1,14.7,14.3,13.9,13.4],'best_page'=>'/guides/overpronation','is_brand'=>false,'opportunity'=>'striking_distance'],
            ['query'=>'carbon plate running shoes','clicks'=>68,'impressions'=>2600,'ctr'=>2.62,'position'=>12.1,'position_trend'=>[14.5,14.0,13.5,13.2,12.8,12.4,12.1],'best_page'=>'/collections/carbon-plate','is_brand'=>false,'opportunity'=>'striking_distance'],
            ['query'=>'nexstage promo code','clicks'=>210,'impressions'=>890,'ctr'=>23.6,'position'=>1.2,'position_trend'=>[1.5,1.4,1.3,1.2,1.2,1.2,1.2],'best_page'=>'/discount','is_brand'=>true,'opportunity'=>null],
            ['query'=>'running shoes for wide feet','clicks'=>62,'impressions'=>2450,'ctr'=>2.53,'position'=>15.7,'position_trend'=>[18.2,17.8,17.2,16.8,16.4,16.0,15.7],'best_page'=>'/collections/wide-fit','is_brand'=>false,'opportunity'=>'striking_distance'],
            ['query'=>'best cushioned running shoes 2026','clicks'=>58,'impressions'=>2200,'ctr'=>2.64,'position'=>14.8,'position_trend'=>[17.1,16.7,16.2,15.8,15.4,15.0,14.8],'best_page'=>'/guides/most-cushioned','is_brand'=>false,'opportunity'=>null],
            ['query'=>'zero drop running shoes','clicks'=>54,'impressions'=>1980,'ctr'=>2.73,'position'=>10.4,'position_trend'=>[13.2,12.8,12.2,11.8,11.2,10.8,10.4],'best_page'=>'/collections/zero-drop','is_brand'=>false,'opportunity'=>null],
            ['query'=>'lightweight running shoes','clicks'=>51,'impressions'=>1850,'ctr'=>2.76,'position'=>11.8,'position_trend'=>[14.0,13.6,13.0,12.5,12.2,11.9,11.8],'best_page'=>'/collections/lightweight','is_brand'=>false,'opportunity'=>'striking_distance'],
            ['query'=>'marathon training shoes','clicks'=>48,'impressions'=>1720,'ctr'=>2.79,'position'=>16.2,'position_trend'=>[19.5,18.9,18.2,17.6,17.1,16.6,16.2],'best_page'=>'/guides/marathon-training','is_brand'=>false,'opportunity'=>null],
            ['query'=>'vegan running shoes','clicks'=>42,'impressions'=>1600,'ctr'=>2.63,'position'=>18.5,'position_trend'=>[22.1,21.4,20.8,20.2,19.6,19.0,18.5],'best_page'=>'/collections/vegan','is_brand'=>false,'opportunity'=>null],
            ['query'=>'nexstage review','clicks'=>188,'impressions'=>720,'ctr'=>26.1,'position'=>2.1,'position_trend'=>[2.8,2.6,2.4,2.3,2.2,2.1,2.1],'best_page'=>'/reviews','is_brand'=>true,'opportunity'=>null],
            ['query'=>'waterproof trail shoes','clicks'=>39,'impressions'=>1520,'ctr'=>2.57,'position'=>20.3,'position_trend'=>[24.5,23.8,23.0,22.4,21.7,21.0,20.3],'best_page'=>'/collections/waterproof','is_brand'=>false,'opportunity'=>null],
            ['query'=>'running shoes with wide toe box','clicks'=>37,'impressions'=>1450,'ctr'=>2.55,'position'=>17.1,'position_trend'=>[20.2,19.6,19.0,18.5,18.0,17.5,17.1],'best_page'=>'/collections/wide-toe','is_brand'=>false,'opportunity'=>null],
            ['query'=>'best shoes for achilles tendinitis','clicks'=>35,'impressions'=>1380,'ctr'=>2.54,'position'=>14.6,'position_trend'=>[17.8,17.3,16.8,16.2,15.7,15.1,14.6],'best_page'=>'/guides/achilles-tendinitis','is_brand'=>false,'opportunity'=>null],
            ['query'=>'cushioned trail shoes','clicks'=>33,'impressions'=>1320,'ctr'=>2.5,'position'=>13.9,'position_trend'=>[16.4,16.0,15.5,15.0,14.6,14.2,13.9],'best_page'=>'/collections/cushioned-trail','is_brand'=>false,'opportunity'=>null],
            ['query'=>'nexstage sizing guide','clicks'=>144,'impressions'=>550,'ctr'=>26.2,'position'=>1.4,'position_trend'=>[1.8,1.6,1.5,1.5,1.4,1.4,1.4],'best_page'=>'/size-guide','is_brand'=>true,'opportunity'=>null],
            ['query'=>'road to trail shoes','clicks'=>31,'impressions'=>1240,'ctr'=>2.5,'position'=>21.4,'position_trend'=>[25.0,24.3,23.5,22.9,22.2,21.8,21.4],'best_page'=>'/collections/road-trail','is_brand'=>false,'opportunity'=>null],
            ['query'=>'plantar fasciitis running shoes','clicks'=>30,'impressions'=>1180,'ctr'=>2.54,'position'=>15.8,'position_trend'=>[18.4,18.0,17.5,17.0,16.5,16.1,15.8],'best_page'=>'/guides/plantar-fasciitis','is_brand'=>false,'opportunity'=>null],
            ['query'=>'stability running shoes','clicks'=>29,'impressions'=>1150,'ctr'=>2.52,'position'=>12.7,'position_trend'=>[15.2,14.8,14.3,13.8,13.3,13.0,12.7],'best_page'=>'/collections/stability','is_brand'=>false,'opportunity'=>null],
            ['query'=>'speed training shoes','clicks'=>28,'impressions'=>1100,'ctr'=>2.55,'position'=>19.2,'position_trend'=>[22.8,22.1,21.5,20.9,20.3,19.7,19.2],'best_page'=>'/collections/speed','is_brand'=>false,'opportunity'=>null],
            ['query'=>'running shoes for beginners','clicks'=>27,'impressions'=>1080,'ctr'=>2.5,'position'=>22.5,'position_trend'=>[26.3,25.5,24.8,24.0,23.4,22.9,22.5],'best_page'=>'/guides/beginners','is_brand'=>false,'opportunity'=>null],
            ['query'=>'nexstage free returns','clicks'=>98,'impressions'=>380,'ctr'=>25.8,'position'=>1.3,'position_trend'=>[1.6,1.5,1.4,1.4,1.3,1.3,1.3],'best_page'=>'/returns','is_brand'=>true,'opportunity'=>null],
            ['query'=>'anti blister running socks','clicks'=>26,'impressions'=>1020,'ctr'=>2.55,'position'=>31.2,'position_trend'=>[38.5,37.0,35.5,34.2,33.0,32.0,31.2],'best_page'=>'/collections/socks','is_brand'=>false,'opportunity'=>null],
            ['query'=>'insoles for running shoes','clicks'=>24,'impressions'=>980,'ctr'=>2.45,'position'=>28.4,'position_trend'=>[35.2,33.8,32.5,31.2,30.0,29.1,28.4],'best_page'=>'/collections/insoles','is_brand'=>false,'opportunity'=>null],
            ['query'=>'ultramarathon shoes','clicks'=>23,'impressions'=>920,'ctr'=>2.5,'position'=>24.8,'position_trend'=>[29.2,28.4,27.6,26.8,26.1,25.4,24.8],'best_page'=>'/collections/ultra','is_brand'=>false,'opportunity'=>null],
            ['query'=>'running shoe comparison 2026','clicks'=>22,'impressions'=>880,'ctr'=>2.5,'position'=>33.5,'position_trend'=>[41.0,39.5,38.0,36.8,35.5,34.4,33.5],'best_page'=>'/comparison','is_brand'=>false,'opportunity'=>null],
            ['query'=>'how to choose running shoes','clicks'=>21,'impressions'=>840,'ctr'=>2.5,'position'=>26.1,'position_trend'=>[30.8,29.9,29.0,28.2,27.5,26.8,26.1],'best_page'=>'/guides/how-to-choose','is_brand'=>false,'opportunity'=>null],
            ['query'=>'eco friendly running shoes','clicks'=>20,'impressions'=>800,'ctr'=>2.5,'position'=>35.8,'position_trend'=>[44.0,42.2,40.5,39.0,37.6,36.7,35.8],'best_page'=>'/collections/eco','is_brand'=>false,'opportunity'=>null],
            ['query'=>'running shoes pronation guide','clicks'=>19,'impressions'=>760,'ctr'=>2.5,'position'=>29.4,'position_trend'=>[34.8,33.6,32.4,31.4,30.4,29.9,29.4],'best_page'=>'/guides/pronation','is_brand'=>false,'opportunity'=>null],
            ['query'=>'what is heel drop in running shoes','clicks'=>18,'impressions'=>720,'ctr'=>2.5,'position'=>38.2,'position_trend'=>[46.8,45.0,43.3,41.7,40.2,39.1,38.2],'best_page'=>'/guides/heel-drop','is_brand'=>false,'opportunity'=>null],
            ['query'=>'nexstage EU shipping','clicks'=>76,'impressions'=>290,'ctr'=>26.2,'position'=>1.5,'position_trend'=>[2.0,1.8,1.7,1.6,1.6,1.5,1.5],'best_page'=>'/shipping','is_brand'=>true,'opportunity'=>null],
            ['query'=>'can you wash running shoes','clicks'=>17,'impressions'=>680,'ctr'=>2.5,'position'=>42.6,'position_trend'=>[52.1,50.0,48.2,46.5,44.9,43.7,42.6],'best_page'=>'/guides/cleaning','is_brand'=>false,'opportunity'=>null],
            ['query'=>'running shoe lifespan km','clicks'=>16,'impressions'=>640,'ctr'=>2.5,'position'=>44.1,'position_trend'=>[54.0,51.8,50.0,48.2,46.6,45.3,44.1],'best_page'=>'/guides/when-replace','is_brand'=>false,'opportunity'=>null],
            ['query'=>'nexstage loyalty program','clicks'=>64,'impressions'=>246,'ctr'=>26.0,'position'=>1.6,'position_trend'=>[2.2,2.0,1.9,1.8,1.7,1.7,1.6],'best_page'=>'/loyalty','is_brand'=>true,'opportunity'=>null],
            ['query'=>'heel strike vs forefoot running','clicks'=>15,'impressions'=>600,'ctr'=>2.5,'position'=>47.3,'position_trend'=>[57.8,55.5,53.3,51.4,49.6,48.3,47.3],'best_page'=>'/guides/running-form','is_brand'=>false,'opportunity'=>null],
            ['query'=>'orthotic friendly running shoes','clicks'=>14,'impressions'=>560,'ctr'=>2.5,'position'=>22.8,'position_trend'=>[27.0,26.2,25.5,24.8,24.1,23.4,22.8],'best_page'=>'/collections/orthotics','is_brand'=>false,'opportunity'=>null],
            ['query'=>'running cadence improve','clicks'=>13,'impressions'=>520,'ctr'=>2.5,'position'=>51.2,'position_trend'=>[62.4,59.8,57.4,55.2,53.2,52.1,51.2],'best_page'=>'/guides/cadence','is_brand'=>false,'opportunity'=>null],
            ['query'=>'nexstage sale','clicks'=>112,'impressions'=>432,'ctr'=>25.9,'position'=>1.4,'position_trend'=>[1.9,1.7,1.6,1.5,1.5,1.4,1.4],'best_page'=>'/sale','is_brand'=>true,'opportunity'=>null],
            ['query'=>'running shoes arch support women','clicks'=>36,'impressions'=>1420,'ctr'=>2.54,'position'=>16.4,'position_trend'=>[19.6,19.0,18.4,17.9,17.4,16.9,16.4],'best_page'=>'/collections/arch-women','is_brand'=>false,'opportunity'=>'leaking'],
            ['query'=>'how to break in running shoes','clicks'=>12,'impressions'=>480,'ctr'=>2.5,'position'=>54.8,'position_trend'=>[66.8,64.0,61.6,59.3,57.1,55.9,54.8],'best_page'=>'/guides/break-in','is_brand'=>false,'opportunity'=>null],
            ['query'=>'running shoe drops explained','clicks'=>11,'impressions'=>440,'ctr'=>2.5,'position'=>58.1,'position_trend'=>[70.8,68.0,65.3,62.9,60.7,59.2,58.1],'best_page'=>'/guides/heel-drop','is_brand'=>false,'opportunity'=>null],
            ['query'=>'nexstage gift card','clicks'=>48,'impressions'=>186,'ctr'=>25.8,'position'=>1.7,'position_trend'=>[2.3,2.1,2.0,1.9,1.8,1.8,1.7],'best_page'=>'/gift-cards','is_brand'=>true,'opportunity'=>null],
            ['query'=>'breathable running shoes summer','clicks'=>38,'impressions'=>1500,'ctr'=>2.53,'position'=>18.9,'position_trend'=>[22.4,21.8,21.1,20.5,19.9,19.4,18.9],'best_page'=>'/collections/summer','is_brand'=>false,'opportunity'=>null],
            ['query'=>'running shoe width guide','clicks'=>10,'impressions'=>400,'ctr'=>2.5,'position'=>61.4,'position_trend'=>[74.9,71.9,69.1,66.5,64.1,62.7,61.4],'best_page'=>'/size-guide','is_brand'=>false,'opportunity'=>null],
            ['query'=>'high arched feet running shoes','clicks'=>32,'impressions'=>1260,'ctr'=>2.54,'position'=>19.7,'position_trend'=>[23.5,22.8,22.1,21.4,20.8,20.2,19.7],'best_page'=>'/collections/high-arch','is_brand'=>false,'opportunity'=>null],
            ['query'=>'best long distance running shoes','clicks'=>44,'impressions'=>1740,'ctr'=>2.53,'position'=>13.2,'position_trend'=>[15.8,15.4,14.9,14.5,14.0,13.6,13.2],'best_page'=>'/collections/long-distance','is_brand'=>false,'opportunity'=>null],
            ['query'=>'cross training shoe running','clicks'=>9,'impressions'=>360,'ctr'=>2.5,'position'=>64.8,'position_trend'=>[79.0,75.8,72.8,70.0,67.5,66.0,64.8],'best_page'=>'/collections/cross-training','is_brand'=>false,'opportunity'=>null],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function mockPages(): array
    {
        return [
            ['page'=>'https://example.com/collections/running/flat-feet','clicks'=>312,'impressions'=>10200,'ctr'=>3.06,'position'=>9.2,'top_query'=>'best running shoes for flat feet','position_trend'=>[11.2,10.8,10.4,10.0,9.8,9.5,9.2]],
            ['page'=>'https://example.com/collections/trail-women','clicks'=>248,'impressions'=>9800,'ctr'=>2.53,'position'=>12.1,'top_query'=>'trail running shoes women','position_trend'=>[14.5,14.0,13.5,13.2,12.8,12.4,12.1]],
            ['page'=>'https://example.com/','clicks'=>580,'impressions'=>3200,'ctr'=>18.1,'position'=>2.2,'top_query'=>'nexstage shoes','position_trend'=>[2.8,2.6,2.4,2.3,2.2,2.2,2.2]],
            ['page'=>'https://example.com/collections/minimalist','clicks'=>198,'impressions'=>7800,'ctr'=>2.54,'position'=>10.4,'top_query'=>'minimalist running shoes','position_trend'=>[12.6,12.2,11.8,11.4,11.0,10.7,10.4]],
            ['page'=>'https://example.com/guides/overpronation','clicks'=>185,'impressions'=>7200,'ctr'=>2.57,'position'=>14.2,'top_query'=>'shoes for overpronation','position_trend'=>[17.1,16.7,16.2,15.7,15.2,14.7,14.2]],
            ['page'=>'https://example.com/collections/carbon-plate','clicks'=>174,'impressions'=>6800,'ctr'=>2.56,'position'=>12.8,'top_query'=>'carbon plate running shoes','position_trend'=>[15.4,15.0,14.5,14.1,13.6,13.2,12.8]],
            ['page'=>'https://example.com/discount','clicks'=>410,'impressions'=>1740,'ctr'=>23.6,'position'=>1.4,'top_query'=>'nexstage promo code','position_trend'=>[1.8,1.6,1.5,1.5,1.4,1.4,1.4]],
            ['page'=>'https://example.com/collections/wide-fit','clicks'=>162,'impressions'=>6400,'ctr'=>2.53,'position'=>16.0,'top_query'=>'running shoes for wide feet','position_trend'=>[19.2,18.7,18.2,17.7,17.2,16.6,16.0]],
            ['page'=>'https://example.com/guides/most-cushioned','clicks'=>148,'impressions'=>5800,'ctr'=>2.55,'position'=>15.4,'top_query'=>'best cushioned running shoes 2026','position_trend'=>[18.4,17.9,17.4,16.9,16.4,15.9,15.4]],
            ['page'=>'https://example.com/reviews','clicks'=>312,'impressions'=>1200,'ctr'=>26.0,'position'=>2.4,'top_query'=>'nexstage review','position_trend'=>[3.0,2.8,2.7,2.6,2.5,2.5,2.4]],
            ['page'=>'https://example.com/collections/zero-drop','clicks'=>136,'impressions'=>5200,'ctr'=>2.62,'position'=>11.0,'top_query'=>'zero drop running shoes','position_trend'=>[13.8,13.4,12.9,12.5,12.0,11.5,11.0]],
            ['page'=>'https://example.com/size-guide','clicks'=>298,'impressions'=>1140,'ctr'=>26.1,'position'=>1.6,'top_query'=>'nexstage sizing guide','position_trend'=>[2.2,2.0,1.9,1.8,1.7,1.7,1.6]],
            ['page'=>'https://example.com/collections/long-distance','clicks'=>128,'impressions'=>4800,'ctr'=>2.67,'position'=>13.8,'top_query'=>'best long distance running shoes','position_trend'=>[16.5,16.0,15.5,15.0,14.5,14.1,13.8]],
            ['page'=>'https://example.com/collections/summer','clicks'=>118,'impressions'=>4600,'ctr'=>2.57,'position'=>19.4,'top_query'=>'breathable running shoes summer','position_trend'=>[23.2,22.5,21.8,21.1,20.5,19.9,19.4]],
            ['page'=>'https://example.com/collections/arch-women','clicks'=>108,'impressions'=>4200,'ctr'=>2.57,'position'=>16.8,'top_query'=>'running shoes arch support women','position_trend'=>[20.2,19.6,19.0,18.4,17.8,17.3,16.8]],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function mockCountries(): array
    {
        return [
            ['country_code'=>'US','country_name'=>'United States','clicks'=>6820,'impressions'=>188000,'ctr'=>3.63,'position'=>13.8],
            ['country_code'=>'GB','country_name'=>'United Kingdom','clicks'=>2140,'impressions'=>61200,'ctr'=>3.50,'position'=>14.6],
            ['country_code'=>'CA','country_name'=>'Canada','clicks'=>1480,'impressions'=>42000,'ctr'=>3.52,'position'=>15.2],
            ['country_code'=>'AU','country_name'=>'Australia','clicks'=>1120,'impressions'=>32400,'ctr'=>3.46,'position'=>16.1],
            ['country_code'=>'DE','country_name'=>'Germany','clicks'=>820,'impressions'=>23800,'ctr'=>3.45,'position'=>15.8],
            ['country_code'=>'FR','country_name'=>'France','clicks'=>640,'impressions'=>18600,'ctr'=>3.44,'position'=>16.4],
            ['country_code'=>'NL','country_name'=>'Netherlands','clicks'=>420,'impressions'=>12200,'ctr'=>3.44,'position'=>15.9],
            ['country_code'=>'NZ','country_name'=>'New Zealand','clicks'=>380,'impressions'=>11000,'ctr'=>3.45,'position'=>16.7],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function mockDevices(): array
    {
        return [
            ['device'=>'Mobile','clicks'=>7862,'impressions'=>218240,'ctr'=>3.60,'position'=>14.8],
            ['device'=>'Desktop','clicks'=>5636,'impressions'=>158840,'ctr'=>3.55,'position'=>13.1],
            ['device'=>'Tablet','clicks'=>1322,'impressions'=>34920,'ctr'=>3.79,'position'=>15.4],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function mockMoversUp(): array
    {
        return [
            ['query'=>'trail running shoes women','position_now'=>11.2,'position_prev'=>14.8,'delta'=>-3.6,'clicks'=>95,'impressions'=>3800],
            ['query'=>'minimalist running shoes','position_now'=>9.8,'position_prev'=>12.9,'delta'=>-3.1,'clicks'=>87,'impressions'=>3100],
            ['query'=>'carbon plate running shoes','position_now'=>12.1,'position_prev'=>15.0,'delta'=>-2.9,'clicks'=>68,'impressions'=>2600],
            ['query'=>'zero drop running shoes','position_now'=>10.4,'position_prev'=>13.0,'delta'=>-2.6,'clicks'=>54,'impressions'=>1980],
            ['query'=>'stability running shoes','position_now'=>12.7,'position_prev'=>15.2,'delta'=>-2.5,'clicks'=>29,'impressions'=>1150],
            ['query'=>'best running shoes for flat feet','position_now'=>8.4,'position_prev'=>10.7,'delta'=>-2.3,'clicks'=>128,'impressions'=>4200],
            ['query'=>'lightweight running shoes','position_now'=>11.8,'position_prev'=>14.0,'delta'=>-2.2,'clicks'=>51,'impressions'=>1850],
            ['query'=>'cushioned trail shoes','position_now'=>13.9,'position_prev'=>15.8,'delta'=>-1.9,'clicks'=>33,'impressions'=>1320],
            ['query'=>'best long distance running shoes','position_now'=>13.2,'position_prev'=>15.0,'delta'=>-1.8,'clicks'=>44,'impressions'=>1740],
            ['query'=>'shoes for overpronation','position_now'=>13.4,'position_prev'=>15.1,'delta'=>-1.7,'clicks'=>74,'impressions'=>2900],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function mockMoversDown(): array
    {
        return [
            ['query'=>'running shoes for beginners','position_now'=>22.5,'position_prev'=>18.8,'delta'=>3.7,'clicks'=>27,'impressions'=>1080],
            ['query'=>'marathon training shoes','position_now'=>16.2,'position_prev'=>13.4,'delta'=>2.8,'clicks'=>48,'impressions'=>1720],
            ['query'=>'vegan running shoes','position_now'=>18.5,'position_prev'=>16.0,'delta'=>2.5,'clicks'=>42,'impressions'=>1600],
            ['query'=>'waterproof trail shoes','position_now'=>20.3,'position_prev'=>18.0,'delta'=>2.3,'clicks'=>39,'impressions'=>1520],
            ['query'=>'speed training shoes','position_now'=>19.2,'position_prev'=>17.1,'delta'=>2.1,'clicks'=>28,'impressions'=>1100],
            ['query'=>'road to trail shoes','position_now'=>21.4,'position_prev'=>19.5,'delta'=>1.9,'clicks'=>31,'impressions'=>1240],
            ['query'=>'ultramarathon shoes','position_now'=>24.8,'position_prev'=>23.0,'delta'=>1.8,'clicks'=>23,'impressions'=>920],
            ['query'=>'running shoes with wide toe box','position_now'=>17.1,'position_prev'=>15.4,'delta'=>1.7,'clicks'=>37,'impressions'=>1450],
            ['query'=>'plantar fasciitis running shoes','position_now'=>15.8,'position_prev'=>14.2,'delta'=>1.6,'clicks'=>30,'impressions'=>1180],
            ['query'=>'breathable running shoes summer','position_now'=>18.9,'position_prev'=>17.4,'delta'=>1.5,'clicks'=>38,'impressions'=>1500],
        ];
    }
}
