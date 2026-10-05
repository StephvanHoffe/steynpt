<?php

declare(strict_types=1);

namespace App\Content;

use App\Site\Locale;

/**
 * De Engelse versie van de website-teksten.
 *
 * Elke pagina uit App\Content\Registry heeft een Engelse tegenhanger met dezelfde opbouw en de Engelse
 * standaardteksten hieronder (PAGES). In het beheer is die als aparte taal te bewerken; opgeslagen Engelse teksten
 * staan onder "en-<pagina>.<onderdeel>.<veld>". Velden die in beide talen gelijk zijn (prijzen, aan/uit, links,
 * webadressen, adres en contactgegevens) bestaan alleen in het Nederlands en worden in het Engels overgenomen.
 * Bij een lijst met zulke velden (de pakketten met hun prijs) volgt het aantal het Nederlands.
 */
final class English
{
    public const PREFIX = 'en-';

    private const NEUTRAL_KINDS = ['check', 'link', 'url', 'price'];

    /** Regels die in beide talen gelijk zijn. */
    public const NEUTRAL_KEYS = [
        'algemeen.locatie.name', 'algemeen.locatie.street', 'algemeen.locatie.city', 'algemeen.locatie.area',
        'algemeen.locatie.phone', 'algemeen.locatie.email', 'algemeen.locatie.instagramHandle',
    ];

    /** Engelse standaardteksten: [pagina => [onderdeel => [veld => waarde]]], zonder de gedeelde velden. */
    public const PAGES = [
        'home' => [
            'hero' => [
                'eyebrow' => 'Personal training · Amsterdam Oud-West & online',
                'title' => "Stronger body.\n*Healthier* life.",
                'intro' => 'I\'m Steyn van Leeuwen, personal trainer and orthomolecular nutritional therapist. I help you build a healthier lifestyle, guide you one-to-one towards your specific goal and coach athletes to their best performance. Now also online, wherever you are.',
                'primary' => 'Start online coaching',
                'secondary' => 'Free intro session',
                'stats' => [
                    ['value' => '1-to-1', 'label' => 'personal attention'],
                    ['value' => 'Online', 'label' => 'wherever you are'],
                    ['value' => '24 hours', 'label' => 'response time'],
                    ['value' => '100%', 'label' => 'commitment to your goal'],
                ],
                'badgeLabel' => 'New',
                'badgeText' => 'Online coaching from €{online-vanaf} per month',
            ],
            'diensten' => ['list' => ['Personal training', 'Online coaching', 'Breathwork', 'Nutrition coaching', 'Elite sport', 'Lifestyle']],
            'online' => [
                'eyebrow' => 'New at SteynPT',
                'title' => 'Online coaching. *Your coach*, anytime, anywhere.',
                'intro' => 'The same personal approach as in the gym, now in your own online dashboard. You get a tailored plan, check in every week and Steyn fine-tunes along the way. Ideal if you train on your own, travel a lot or want extra guidance alongside your PT sessions.',
                'features' => [
                    ['title' => 'Tailored training plan', 'text' => 'Matched to your goal, level and schedule.'],
                    ['title' => 'Nutrition plan', 'text' => 'The right balance of macro- and micronutrients.'],
                    ['title' => 'Check-ins & measurements', 'text' => 'Your progress at a glance in your dashboard.'],
                    ['title' => 'Direct contact', 'text' => 'Steyn adjusts your plan where needed.'],
                ],
                'primary' => 'View the packages',
                'secondary' => 'Create a free account',
            ],
            'aanbod' => [
                'eyebrow' => 'Services',
                'title' => 'One coach, everything for your goal',
                'intro' => 'With a wide range of qualifications and years of experience, Steyn dares to guarantee results for everyone. Are you ready?',
                'button' => 'All prices',
                'services' => [
                    [
                        'title' => 'Online coaching',
                        'text' => 'Training plan, nutrition plan and weekly check-ins in your own dashboard. Train where and when you want.',
                    ],
                    [
                        'title' => 'Personal training',
                        'text' => 'One-to-one training for a healthier lifestyle. Whatever your goal or sport, SteynPT gives it 100%.',
                    ],
                    [
                        'title' => 'Elite sport & specific goals',
                        'text' => 'Sport-specific coaching, periodisation and injury prevention for athletes who want more.',
                    ],
                    [
                        'title' => 'Breathwork',
                        'text' => 'Work on calm, focus, recovery and energy, one-to-one or in a group. Group sessions for teams, companies and groups of friends on request.',
                    ],
                    [
                        'title' => 'Nutrition coaching',
                        'text' => 'For losing or gaining weight. Orthomolecular, lifestyle and vitality coaching.',
                    ],
                ],
                'onlinePoints' => [
                    'Tailored training and nutrition plan',
                    'Weekly check-in and monthly review',
                    'Appointments and progress in your dashboard',
                    'From €{online-vanaf} per month',
                ],
                'badge' => 'New',
                'more' => 'Read more',
            ],
            'over' => [
                'eyebrow' => 'Meet Steyn',
                'title' => 'Hi, I\'m Steyn van Leeuwen',
                'intro' => 'Full-time personal trainer and nutrition coach, born in Hoevelaken and based in Amsterdam. An early-stage herniated disc from playing hockey led me to strength training. That\'s where I found my passion, and the drive to bring clarity to the fitness world.',
                'cardTitle' => '15 years old',
                'cardText' => 'That\'s how old I was when my physio advised me to start strength training.',
                'button' => 'Read my story',
            ],
            'werkwijze' => [
                'eyebrow' => 'How it works',
                'title' => 'From intake to results',
                'intro' => 'Whether you train in the gym or online, every collaboration starts with an intake and a tailored plan.',
            ],
            'vriendenactie' => [
                'eyebrow' => 'Refer a friend',
                'title' => 'Bring a friend. *{actie}.*',
                'intro' => 'Invite a friend to online coaching. Your friend gets {vriendkorting}; you get {jouwkorting} as soon as your friend starts.',
                'primary' => 'Create a free account',
                'secondary' => 'How it works',
            ],
            'reviews' => ['eyebrow' => 'Reviews', 'title' => 'What clients say'],
            'locaties' => [
                'eyebrow' => 'Visit us',
                'title' => 'Train wherever suits you',
                'intro' => 'At Gymbase on the Overtoom in Amsterdam Oud-West, at your favourite spot or completely online.',
            ],
            'seo' => [
                'title' => 'SteynPT · Personal trainer and online coaching in Amsterdam',
                'description' => 'English-speaking personal trainer at Gymbase on the Overtoom in Amsterdam Oud-West, close to the Vondelpark. One-to-one training, online coaching, nutrition coaching and breathwork.',
            ],
        ],
        'algemeen' => [
            'aankondiging' => ['label' => 'New', 'text' => 'Online coaching: invite a friend. {actie}.'],
            'vriendenactie' => [
                'headline' => '50% off together',
                'friendReward' => '50% off the first month of online coaching',
                'referrerReward' => '50% off a month of online coaching',
                'steps' => [
                    [
                        'title' => 'Share your personal link',
                        'text' => 'You\'ll find it in My account and can share it with one tap via WhatsApp or email.',
                    ],
                    ['title' => 'Your friend signs up', 'text' => 'Through your link, your friend gets {vriendkorting}.'],
                    [
                        'title' => 'Your friend starts online coaching',
                        'text' => 'You then get {jouwkorting}. Steyn deducts it from your next invoice.',
                    ],
                ],
            ],
            'afsluiter' => [
                'title' => 'Ready to start?',
                'text' => 'Create a free account, choose your package and Steyn will get in touch within 24 hours.',
                'primary' => 'Start online coaching',
                'secondary' => 'Free intro session',
            ],
            'reviews' => [
                'reviews' => [
                    [
                        'quote' => 'Steyn taught me that it\'s not just about losing weight, but about becoming aware of your lifestyle.',
                        'name' => 'Miranda \'d Weegman',
                        'role' => 'Surgical assistant',
                    ],
                    [
                        'quote' => 'I\'d been trying to lose weight for years but always stayed around the same weight. Since training with Steyn, I\'m finally getting results.',
                        'name' => 'Steve van Maanen',
                        'role' => 'Baker',
                    ],
                ],
            ],
            'werkwijze' => [
                'steps' => [
                    [
                        'title' => 'Intake',
                        'text' => 'We start with a conversation about your goals, your background and what you\'ve tried so far.',
                    ],
                    [
                        'title' => 'Baseline measurement',
                        'text' => 'We weigh, measure and assess how you move, so we know exactly what to work on.',
                    ],
                    [
                        'title' => 'Tailored plan',
                        'text' => 'You get a personal training plan and nutrition plan with the ideal balance of macro- and micronutrients.',
                    ],
                    [
                        'title' => 'Coaching',
                        'text' => 'We stay in touch between sessions too. We keep adjusting until you reach your goal, and beyond.',
                    ],
                ],
            ],
            'expertises' => [
                'list' => [
                    'Personal trainer',
                    'Orthomolecular nutritional therapist',
                    'Lifestyle and vitality coaching',
                    'Breathwork',
                    'Powerlifting',
                    'Boxing',
                    'CrossFit',
                    'Sport-specific training',
                ],
            ],
            'locatie' => [
                'directions' => 'A three-minute walk from the Vondelpark. Tram 1 stops around the corner, at the Rhijnvis Feithstraat stop.',
                'onLocationTitle' => 'On location',
                'onLocation' => 'Besides our regular location at Gymbase, we also come to you: in your favourite park or at your workplace.',
                'onlineTitle' => 'Online',
                'online' => 'With online coaching you train where and when you want, with Steyn always within reach.',
                'responseTime' => 'We aim to get in touch within 24 hours.',
            ],
            'footer' => [
                'intro' => 'Personal training, nutrition coaching and breathwork in Amsterdam Oud-West. Online coaching wherever you are.',
                'extra' => 'On location & online',
                'copyright' => 'SteynPT · Personal training Amsterdam',
            ],
        ],
        'pakketten' => [
            'online' => [
                'plans' => [
                    [
                        'name' => 'Start',
                        'tagline' => 'Train on your own with a plan that works',
                        'features' => [
                            'Tailored training plan',
                            'Nutrition guidelines based on your goal',
                            'Monthly review and plan update',
                            'Weekly check-in in your dashboard',
                            'Your progress and measurements in your dashboard',
                        ],
                    ],
                    [
                        'name' => 'Pro',
                        'tagline' => 'Weekly guidance for maximum results',
                        'features' => [
                            'Everything in Start',
                            'Personal nutrition plan (macros & micros)',
                            'Weekly feedback from Steyn on your check-in',
                            'Contact in between on weekdays',
                            'Plan updates whenever you need them',
                        ],
                    ],
                    [
                        'name' => 'Performance',
                        'tagline' => 'For specific goals and (elite) athletes',
                        'features' => [
                            'Everything in Pro',
                            'Two video calls a month',
                            'Sport-specific periodisation',
                            'Technique analysis based on your videos',
                            'Breathing protocol for focus and recovery',
                        ],
                    ],
                ],
            ],
            'pt' => [
                'cards' => [
                    [
                        'label' => 'Personal training',
                        'name' => 'Single session',
                        'unit' => 'per hour',
                        'features' => ['Improve your health', 'Flexible times', 'Step by step towards your goal', 'Tailor-made'],
                        'note' => 'Duo training: €15 surcharge per session',
                    ],
                    [
                        'label' => '10× one-to-one training',
                        'name' => 'Introduction package',
                        'unit' => '',
                        'features' => ['Intake consultation', 'Start and end measurement', 'Movement assessment', 'Weekly nutrition advice'],
                        'note' => '',
                    ],
                    [
                        'label' => '20× one-to-one training',
                        'name' => 'Health package',
                        'unit' => '',
                        'features' => [
                            'Intake consultation',
                            'Start, interim and end measurement',
                            'Movement assessment',
                            'Improve your health',
                            'Change your lifestyle',
                        ],
                        'note' => '',
                    ],
                    [
                        'label' => '40× one-to-one training',
                        'name' => 'Lifechanger package',
                        'unit' => '',
                        'features' => [
                            'Intake consultation',
                            'Start, interim and end measurement',
                            'Movement assessment',
                            'Improve your health',
                            'Change your lifestyle',
                        ],
                        'note' => '',
                    ],
                ],
            ],
            'adem' => [
                'name' => 'One-to-one breathwork session',
                'label' => 'Breathwork',
                'duration' => '1.5 hours',
                'unit' => 'per session of {ademduur}',
                'features' => [
                    'Personal guidance from Steyn',
                    'Tailored to your needs: stress, sleep, sport or recovery',
                    'Exercises to continue on your own',
                    'No experience needed',
                ],
            ],
            'labels' => ['featured' => 'Most popular'],
        ],
        'online-coaching' => [
            'hero' => [
                'eyebrow' => 'New · Online coaching',
                'title' => "Your coach.\n*Anytime*, anywhere.",
                'intro' => 'The personal SteynPT approach, now online too. A tailored plan, weekly check-ins in your own dashboard and a coach who thinks along with you, wherever you train.',
                'primary' => 'Choose your package',
                'secondary' => 'Create a free account',
                'note' => 'Contact for your intake within 24 hours · You only pay after the intake',
            ],
            'voorWie' => [
                'eyebrow' => 'Who it\'s for',
                'title' => 'Made for people who want to go further',
                'intro' => 'Whether you\'re just starting out or have been training for years, online coaching gives you structure, knowledge and someone to keep you on track.',
                'cards' => [
                    [
                        'title' => 'You train on your own',
                        'text' => 'You want a plan that truly fits you, and someone keeping an eye on it.',
                    ],
                    ['title' => 'You have a busy schedule', 'text' => 'Train whenever it suits you: at home, in the gym or at the office.'],
                    ['title' => 'You travel a lot', 'text' => 'Your coaching travels with you, wherever you are.'],
                    ['title' => 'You have a specific goal', 'text' => 'A competition, a season or a personal record: we plan towards it.'],
                ],
            ],
            'stappen' => [
                'eyebrow' => 'How it works',
                'title' => 'Get started in four steps',
                'steps' => [
                    ['title' => 'Create an account', 'text' => 'Choose your package and create your free account in two minutes.'],
                    [
                        'title' => 'Intake',
                        'text' => 'Steyn gets in touch within 24 hours to schedule an intake by video call or at Gymbase.',
                    ],
                    [
                        'title' => 'Your plan',
                        'text' => 'You receive your training plan and nutrition plan, tailored to your goal and schedule.',
                    ],
                    [
                        'title' => 'Check in & adjust',
                        'text' => 'Every week you check in through your dashboard. Steyn adjusts your plan and keeps track of your measurements.',
                    ],
                ],
            ],
            'pakketten' => [
                'eyebrow' => 'Packages',
                'title' => 'Choose what suits you',
                'intro' => 'All packages include a personal dashboard, weekly check-ins and your measurements in one overview.',
                'note' => 'Prefer to meet first?',
                'noteLink' => 'Book a free intro session',
            ],
            'vriendenactie' => [
                'eyebrow' => 'Refer a friend',
                'title' => '{actie}',
                'intro' => 'Training together is more fun and keeps you both sharp. Invite a friend with your personal link in My account.',
                'button' => 'Terms and details',
            ],
            'faq' => [
                'eyebrow' => 'Frequently asked questions',
                'title' => 'Good to know',
                'questions' => [
                    [
                        'q' => 'Do I need a gym?',
                        'a' => 'No. Your plan is tailored to where you train: in the gym, at home with limited equipment or outdoors.',
                    ],
                    [
                        'q' => 'How soon will I hear back after signing up?',
                        'a' => 'Steyn aims to get in touch within 24 hours to schedule your intake. You only pay if you decide to start after the intake.',
                    ],
                    [
                        'q' => 'Can I combine online coaching with personal training?',
                        'a' => 'Definitely. Many clients combine a few one-to-one sessions in Amsterdam with online coaching for the days in between. Discuss it during your intake.',
                    ],
                    [
                        'q' => 'How long does a programme last?',
                        'a' => 'We tailor it to your goal. For lasting results, Steyn recommends allowing at least three months.',
                    ],
                    [
                        'q' => 'How does Refer a friend work?',
                        'a' => 'Every client has a personal invite link. A friend who signs up through that link gets {vriendkorting}. When your friend starts, you get {jouwkorting}.',
                    ],
                ],
            ],
            'afsluiter' => [
                'title' => 'Start today',
                'text' => 'Create your free account, choose your package and Steyn will get in touch within 24 hours.',
                'primary' => 'Create account',
                'secondary' => 'Free intro session',
            ],
            'seo' => [
                'title' => 'Online personal trainer: tailored training and nutrition',
                'description' => 'Online coaching by personal trainer Steyn van Leeuwen: a tailored training and nutrition plan and weekly check-ins in your own dashboard. From €{online-vanaf} per month.',
            ],
        ],
        'personal-training' => [
            'hero' => [
                'eyebrow' => 'English-speaking personal trainer in Amsterdam Oud-West',
                'title' => 'One-to-one. *100%* for your goal.',
                'intro' => 'Whatever your goal or sport, SteynPT gives it 100%. With a personal training plan we work towards your goal as efficiently as possible, with plenty of energy, attention to proper technique and a great atmosphere. You train at Gymbase on the Overtoom, a few minutes from the Vondelpark, or at a place that suits you.',
                'primary' => 'Book a free intro session',
                'secondary' => 'View the packages',
            ],
            'leefstijl' => [
                'eyebrow' => 'For a healthier lifestyle',
                'title' => 'A clear plan, carried out together',
                'body' => "I create a personalised training plan for you, so we work towards your goal as efficiently as possible. With a clear, well-structured plan you know exactly what to expect and what to do to reach your goal.\n\nBesides experience in strength training, I have a background in powerlifting, boxing, CrossFit and sport-specific training. If you like, we combine them in a way that gets you to your goal.",
                'cardTitle' => 'Always included',
                'cardList' => [
                    'Intake consultation about your goals and background',
                    'Baseline measurement: weight, measurements and movement',
                    'Personal training plan',
                    'Nutrition advice based on your goal',
                    'Contact between sessions too',
                    'Training at Gymbase (Overtoom, Oud-West) or on location',
                ],
            ],
            'topsport' => [
                'eyebrow' => 'Specific goals & elite sport',
                'title' => 'Coaching for athletes who want *more*',
                'intro' => 'Working towards a competition, coming back from an injury or chasing those last few percent? Steyn specialises in one-to-one coaching for (elite) athletes and people with a specific goal.',
                'cards' => [
                    [
                        'title' => 'Goal-driven periodisation',
                        'text' => 'A plan that builds towards your competition, season or big moment.',
                    ],
                    ['title' => 'Sport-specific strength', 'text' => 'Strength, speed and power translated to your sport.'],
                    [
                        'title' => 'Injury prevention',
                        'text' => 'Movement assessment and targeted exercises to stay strong and injury-free.',
                    ],
                    [
                        'title' => 'Recovery & breathing',
                        'text' => 'Sleep, nutrition and breathing techniques for optimal recovery and focus under pressure.',
                    ],
                    ['title' => 'Technique analysis', 'text' => 'We analyse your technique and adjust, also in between sessions.'],
                    ['title' => 'Fits your season', 'text' => 'Aligned with your club training, competitions and travel.'],
                ],
                'primary' => 'Discuss your goal',
                'secondary' => 'Combine with online coaching',
            ],
            'tarieven' => [
                'eyebrow' => 'Prices',
                'title' => 'One-to-one packages',
                'intro' => 'Prefer to train one-to-one and get the most out of every session with Steyn? Choose one of the packages.',
                'button' => 'Request this package',
            ],
            'reviews' => ['eyebrow' => 'Reviews', 'title' => 'Results that last'],
            'faq' => [
                'eyebrow' => 'Frequently asked questions',
                'title' => 'Questions about personal training in Amsterdam',
                'questions' => [
                    [
                        'q' => 'How much does a personal trainer at SteynPT cost?',
                        'a' => 'That depends on how many sessions you take. You choose a single session or a package of 10, 20 or 40 sessions; the bigger the package, the lower the price per session. All prices are listed above and on the pricing page.',
                    ],
                    [
                        'q' => 'Where do the sessions take place?',
                        'a' => 'At Gymbase on the Overtoom in Amsterdam Oud-West, a few minutes\' walk from the Vondelpark. Prefer somewhere else? Then we train on location, for example in the park or at your workplace.',
                    ],
                    [
                        'q' => 'Is the intro session really free?',
                        'a' => 'Yes. In a free 30-minute intro session we discuss your goals and background, and you get a tour of Gymbase. Afterwards you decide whether you want to start.',
                    ],
                    [
                        'q' => 'Do you train in English?',
                        'a' => 'Yes. Steyn coaches in English and Dutch, so you can train, ask questions and receive your plans in English.',
                    ],
                    [
                        'q' => 'I\'ve never trained in a gym. Is personal training for me?',
                        'a' => 'Absolutely. Steyn tailors your training plan to your level and pays attention to proper technique, so you start safely and with confidence. If you\'ve been training for years or have a specific sports goal, you get a tailored plan just the same.',
                    ],
                    [
                        'q' => 'Can we train as a pair?',
                        'a' => 'Yes, duo training is possible. You train together with a friend, partner or colleague, for a surcharge per session. You\'ll find it on the pricing page.',
                    ],
                    [
                        'q' => 'Can I combine personal training with online coaching?',
                        'a' => 'Yes. Many clients combine a few one-to-one sessions at Gymbase with online coaching for the days in between. That way you have a plan and a coach keeping an eye on things between sessions too.',
                    ],
                ],
            ],
            'afsluiter' => [
                'title' => 'Fancy meeting up?',
                'text' => 'Book a free intro session. We\'d love to welcome you at Gymbase.',
                'primary' => 'Free intro session',
                'secondary' => 'Or start online',
            ],
            'seo' => [
                'title' => 'English-speaking personal trainer in Amsterdam Oud-West',
                'description' => 'One-to-one personal training in English at Gymbase, Overtoom 371-w in Amsterdam Oud-West. For a healthier lifestyle, specific goals and elite sport. Also on location.',
            ],
        ],
        'ademcoaching' => [
            'hero' => [
                'eyebrow' => 'Breathwork in Amsterdam, one-to-one and in groups',
                'title' => 'Breathe in. *Calm down.* Perform better.',
                'intro' => 'Your breath is the most powerful tool you always carry with you. In a personal breathwork session of {ademduur} you learn how to use your breath to lower stress, sharpen your focus and recover faster. You can also do it with your team or group: group sessions are available on request.',
                'primary' => 'Book a breathwork session',
                'secondary' => 'Request a group session',
            ],
            'voordelen' => [
                'eyebrow' => 'What it gives you',
                'title' => 'Small change, big effect',
                'intro' => 'Breathwork fits seamlessly with the SteynPT philosophy: a healthy lifestyle isn\'t just about training and nutrition, but also about rest and recovery.',
                'cards' => [
                    ['title' => 'Calm & focus', 'text' => 'Learn to calm your nervous system and stay clear-headed under pressure.'],
                    ['title' => 'Better sleep', 'text' => 'Breathing techniques that help you relax and recover more deeply.'],
                    ['title' => 'More energy', 'text' => 'More efficient breathing gives you more energy throughout the day.'],
                    ['title' => 'Sports performance', 'text' => 'Improve your endurance, recovery between efforts and concentration.'],
                ],
            ],
            'vormen' => [
                'eyebrow' => 'Two formats',
                'title' => 'One-to-one or with your group',
                'soloLabel' => 'One-to-one',
                'perSession' => 'per session',
                'soloButton' => 'Book a breathwork session',
                'groupLabel' => 'In a group',
                'groupTitle' => 'Group session',
                'groupPrice' => 'On request',
                'groupText' => 'We tailor the format, length and price to your group size and location.',
                'groups' => [
                    [
                        'title' => 'Companies',
                        'text' => 'A vitality session at the office or during a team day. An instant antidote to work stress.',
                    ],
                    ['title' => 'Sports teams', 'text' => 'Breath training as part of warm-up, recovery and mental preparation.'],
                    [
                        'title' => 'Friends & groups',
                        'text' => 'Experience something new together, indoors at Gymbase or outdoors in the park.',
                    ],
                ],
                'groupButton' => 'Request a group session',
            ],
            'sessie' => [
                'title' => 'What a session looks like',
                'steps' => [
                    ['title' => 'Explanation', 'text' => 'What happens in your body when you breathe, and why does this work?'],
                    ['title' => 'Practice', 'text' => 'Basic techniques for relaxation, focus and energy that you can use anywhere.'],
                    ['title' => 'Guided breathwork session', 'text' => 'A longer session in which Steyn guides you step by step.'],
                    ['title' => 'Take it home', 'text' => 'You leave with concrete exercises for your daily life or sport.'],
                ],
            ],
            'faq' => [
                'eyebrow' => 'Frequently asked questions',
                'title' => 'Good to know',
                'points' => [
                    'One-to-one: {ademduur} for €{ademprijs}',
                    'Group sessions on request, from 3 people',
                    'No experience needed',
                    'On location, at Gymbase or outdoors',
                ],
                'questions' => [
                    [
                        'q' => 'How long is a one-to-one breathwork session and what does it cost?',
                        'a' => 'A one-to-one breathwork session lasts {ademduur} and costs €{ademprijs}. That leaves room for explanation, practice and a longer guided breathwork session.',
                    ],
                    [
                        'q' => 'How does a group session work?',
                        'a' => 'Group sessions are available on request. Get in touch and we\'ll tailor the format, length and price to your group size and location. A session works best with small to medium-sized groups.',
                    ],
                    [
                        'q' => 'Do I need any experience?',
                        'a' => 'No. Every session starts with an explanation, and the exercises are adapted to beginners and advanced participants alike.',
                    ],
                    [
                        'q' => 'Is breathwork suitable for everyone?',
                        'a' => 'For most people, yes. Are you pregnant, or do you have epilepsy, cardiovascular disease or other medical conditions? Let us know beforehand, so we can adapt the exercises or consult your doctor first.',
                    ],
                    [
                        'q' => 'Where does a session take place?',
                        'a' => 'At Gymbase in Amsterdam, at your office or outdoors, as long as there\'s a quiet place to lie down or sit.',
                    ],
                ],
            ],
            'afsluiter' => [
                'title' => 'Book your breathwork session',
                'text' => 'Come for a personal session, or tell us about your team or group and we\'ll propose a tailored group session.',
                'primary' => 'Book a one-to-one session',
                'secondary' => 'Request a group session',
            ],
            'seo' => [
                'title' => 'Breathwork in Amsterdam, one-to-one and in groups',
                'description' => 'Breathwork coaching in Amsterdam with Steyn van Leeuwen: a one-to-one breathwork session of {ademduur} for €{ademprijs}, or a group session for companies and sports teams.',
            ],
        ],
        'voedingscoaching' => [
            'hero' => [
                'eyebrow' => 'Nutrition coach in Amsterdam Oud-West',
                'title' => 'Nutrition that *works* for you',
                'intro' => 'Based on your goals, I create a targeted nutrition plan for you. Step by step we improve your diet and your health. Orthomolecular, lifestyle and vitality coaching.',
                'primary' => 'Request a free intro session',
            ],
            'begeleiding' => [
                'eyebrow' => 'Nutrition guidance',
                'title' => 'The right balance of macros and micros',
                'body' => "I'm an orthomolecular nutritional therapist and help you find the right balance of macro- and micronutrients. No crash diets, but a plan that fits your life and that you can stick to.\n\nWant to lose weight, gain weight or dealing with health complaints? By adjusting your diet, we can achieve a lot together.",
                'cardTitle' => 'I can help you with, among other things',
                'cardList' => ['Losing or gaining weight', 'Digestive complaints', 'High cholesterol', 'High blood pressure', 'Acne', 'Low energy'],
            ],
            'meten' => [
                'eyebrow' => 'Measuring is knowing',
                'title' => 'Results you can see',
                'intro' => 'We start with a baseline measurement and track your progress along the way, so we know exactly what works.',
                'points' => [
                    'Intake and analysis of your eating habits',
                    'Tailored nutrition plan',
                    'Interim measurements and adjustments',
                    'Part of every PT and online package',
                ],
            ],
            'faq' => [
                'eyebrow' => 'Frequently asked questions',
                'title' => 'Questions about nutrition coaching',
                'questions' => [
                    [
                        'q' => 'What does an orthomolecular nutritional therapist do?',
                        'a' => 'An orthomolecular nutritional therapist looks not only at calories and macronutrients (protein, carbohydrates and fats), but also at micronutrients such as vitamins and minerals. That way we work on your goal and on how you feel.',
                    ],
                    [
                        'q' => 'How does nutrition coaching work?',
                        'a' => 'We start with an intake and an analysis of your eating habits. Then you get a tailored nutrition plan. With interim measurements we see what works and adjust along the way.',
                    ],
                    [
                        'q' => 'Do I have to follow a strict diet?',
                        'a' => 'No. No crash diets, but a plan that fits your life and that you can stick to. Step by step we improve your diet.',
                    ],
                    [
                        'q' => 'Can nutrition coaching be done online?',
                        'a' => 'Yes. Nutrition coaching is part of online coaching: your nutrition and your weekly check-ins are in your own dashboard. If you live in Amsterdam, you can also come by at Gymbase in Oud-West.',
                    ],
                    [
                        'q' => 'I have a medical condition. Is nutrition coaching suitable for me?',
                        'a' => 'Nutrition coaching doesn\'t replace treatment by your doctor. If you take medication or have a condition, mention it during the intake. Where needed, we align the plan with your doctor.',
                    ],
                ],
            ],
            'afsluiter' => [
                'title' => 'Also available online',
                'text' => 'Nutrition coaching is part of online coaching. Your nutrition and your weekly check-ins are simply in your own dashboard.',
                'primary' => 'View online coaching',
                'secondary' => 'Free intro session',
            ],
            'seo' => [
                'title' => 'Nutrition coach in Amsterdam Oud-West',
                'description' => 'Nutrition coaching in English by orthomolecular nutritional therapist Steyn van Leeuwen, in Amsterdam Oud-West or online. For weight loss, weight gain, energy and digestion.',
            ],
        ],
        'tarieven' => [
            'hero' => [
                'eyebrow' => 'Personal training and coaching in Amsterdam',
                'title' => 'Prices',
                'intro' => 'Transparent prices, no surprises. Choose the package that fits your goal, or book a free intro session first.',
                'primary' => 'Free intro session',
            ],
            'menu' => ['online' => 'Online coaching', 'pt' => 'One-to-one training', 'adem' => 'Breathwork'],
            'online' => [
                'eyebrow' => 'New · Online coaching',
                'title' => 'Online coaching',
                'intro' => 'Monthly coaching with your own dashboard, weekly check-ins and your measurements in one overview.',
            ],
            'pt' => [
                'eyebrow' => 'One-to-one training',
                'title' => 'Personal training',
                'intro' => 'Prefer to train one-to-one and get the most out of every session with Steyn? Choose one of the packages.',
                'button' => 'Request this package',
                'note' => '',
            ],
            'adem' => [
                'eyebrow' => 'One-to-one and in groups',
                'title' => 'Breathwork',
                'intro' => 'A personal breathwork session at a fixed price. Group sessions for companies, sports teams and groups of friends are available on request.',
                'soloButton' => 'Book a breathwork session',
                'groupLabel' => 'In a group',
                'groupTitle' => 'Group session',
                'groupPrice' => 'On request',
                'groupText' => 'We tailor the format, length and price to your group size and location.',
                'groupButton' => 'Request a group session',
                'moreLink' => 'More about breathwork',
            ],
            'reviews' => ['eyebrow' => 'Reviews', 'title' => 'What clients say'],
            'seo' => [
                'title' => 'Personal training and coaching prices in Amsterdam',
                'description' => 'All SteynPT prices: one-to-one personal training at Gymbase in Amsterdam Oud-West, online coaching and breathwork (one-to-one and in groups).',
            ],
        ],
        'over-steyn' => [
            'hero' => [
                'eyebrow' => 'Personal trainer in Amsterdam',
                'title' => 'About *Steyn*',
                'intro' => 'Full-time personal trainer and nutrition coach. Born in Hoevelaken, based in Amsterdam, and still learning every day.',
                'primary' => 'Request an intro session',
            ],
            'verhaal' => [
                'eyebrow' => 'My story',
                'title' => 'From herniated disc to passion',
                'body' => "I'm Steyn van Leeuwen, full-time personal trainer and nutrition coach. I was born in Hoevelaken and now work as a PT in Amsterdam. When I was 15, I started strength training: playing hockey had given me an early-stage herniated disc in my lower back, and my physiotherapist recommended it.\n\nI found my passion in it and soon discovered how much confusion there is in the fitness world: everyone has a different opinion. That's where my interest began. I've completed several courses in personal training and nutrition, and I keep learning every day.\n\nI give personal training sessions, create tailored nutrition plans, guide athletes towards specific goals and teach breathwork, one-to-one and in groups. Together we work on your goals: indoors, outdoors, in the gym, at home, at the office or online.\n\nReady to take your health seriously and find out what I can do for you? Get in touch!",
                'expertisesTitle' => 'Expertise',
            ],
            'werkwijze' => [
                'eyebrow' => 'How it works',
                'title' => 'How we work together',
                'intro' => 'I don\'t just coach you during our sessions. We stay in touch in between as well, to take your health to the next level.',
            ],
            'afsluiter' => [
                'title' => 'Let\'s meet!',
                'text' => 'We\'d love to welcome you at Gymbase, or start online right away.',
                'primary' => 'Free intro session',
                'secondary' => 'Start online coaching',
            ],
            'seo' => [
                'title' => 'About Steyn van Leeuwen, personal trainer in Amsterdam',
                'description' => 'Meet Steyn van Leeuwen: full-time personal trainer, nutrition coach and orthomolecular nutritional therapist in Amsterdam, coaching in English and Dutch.',
            ],
        ],
        'vriend-uitnodigen' => [
            'hero' => [
                'eyebrow' => 'Refer a friend to online coaching',
                'title' => 'Bring a friend. *{actie}.*',
                'intro' => 'Already training with Steyn? Invite a friend to online coaching. Your friend gets {vriendkorting} and you get {jouwkorting} as soon as your friend starts.',
                'primary' => 'Go to my invite link',
                'secondary' => 'No account yet? Sign up',
            ],
            'stappen' => [
                'eyebrow' => 'How it works',
                'title' => 'In three steps',
                'intro' => 'Your personal link is in My account. Anyone who signs up through your link is automatically linked to you; your dashboard shows who has signed up and who has already started.',
            ],
            'voorwaarden' => [
                'title' => 'Refer a friend: terms',
                'points' => [
                    'The offer applies to new online coaching clients who sign up through a personal invite link or code.',
                    'The new client gets {vriendkorting}.',
                    'The person who invited them gets {jouwkorting} for every friend who actually starts; Steyn deducts it from a following invoice.',
                    'Discounts cannot be exchanged for cash and cannot be combined with other offers.',
                    'Inviting yourself or creating multiple accounts is not allowed.',
                    'SteynPT may change or end the offer; discounts already earned remain valid.',
                ],
            ],
            'afsluiter' => [
                'title' => 'Not a client yet?',
                'text' => 'Create a free account, choose your package and Steyn will get in touch within 24 hours.',
                'primary' => 'View online coaching',
                'secondary' => 'Free intro session',
            ],
            'seo' => [
                'title' => 'Refer a friend',
                'description' => 'Invite a friend to online coaching at SteynPT. {actie}: your friend gets {vriendkorting}.',
            ],
        ],
        'contact' => [
            'hero' => [
                'eyebrow' => 'Free intro session at Gymbase in Amsterdam',
                'title' => 'We\'d love to *meet* you!',
                'intro' => 'We\'d love to invite you to Gymbase for a free intro session. We\'ll tell you more about how we work, give you a tour and hear all about your expectations and goals.',
                'tipTitle' => 'Did you know',
                'tipText' => 'SteynPT also trains outside the gym?',
            ],
            'formulier' => ['title' => 'Request an intro session', 'intro' => 'Fill in your details and we\'ll get in touch to schedule a time.'],
            'seo' => [
                'title' => 'Contact and free intro session at Gymbase Amsterdam',
                'description' => 'Request a free intro session. You\'re welcome at Gymbase, Overtoom 371-w in Amsterdam Oud-West: close to the Vondelpark, with tram 1 around the corner.',
            ],
        ],
        'privacy' => [
            'intro' => [
                'eyebrow' => 'SteynPT',
                'title' => 'Privacy policy',
                'intro' => 'SteynPT handles your personal data with care and complies with the General Data Protection Regulation (GDPR). Below you can read which data we process and why. This is a translation of the Dutch privacy policy; in case of any difference, the Dutch version applies.',
            ],
            'onderdelen' => [
                'sections' => [
                    [
                        'title' => 'Who are we?',
                        'body' => 'SteynPT is the business of Steyn van Leeuwen, personal trainer in Amsterdam. SteynPT is responsible for processing your personal data as described in this policy. Do you have a question about your privacy or want to make a request? Get in touch via the contact form on this website.',
                    ],
                    [
                        'title' => 'What data do we process?',
                        'body' => "Contact requests: your name, email address, (optionally) phone number, your interest and your message.\n\nAccount: your name, email address, phone number, goal, chosen package, who invited you and whom you have invited (Refer a friend).\n\nAppointments: type, date, time, location and any comment you add. Steyn adds these appointments, with your name and contact details, to his own calendar (for example Google or Apple Calendar) via a secure, secret link.\n\nMeasurements: weight, body fat percentage, muscle mass and circumference measurements that Steyn tracks with you. These are health data; you can see them yourself in My account.\n\nCheck-ins: your weekly scores for energy, sleep and nutrition, number of workouts, and optionally your weight and comments. These are health data; we only process them with your explicit consent and solely for your coaching.\n\nIntake: your goal, sex, year of birth, height, weight, activity level, training experience and preferences, injuries, eating style, allergies and any medical points of attention. These are health data too; we only use them with your explicit consent and only to create your training and nutrition plan.",
                    ],
                    [
                        'title' => 'Use of AI for your plan',
                        'body' => "For a first draft of your training and nutrition plan, we use the AI model Claude by Anthropic. We only send the intake data needed for the plan, without your name, email address or phone number.\n\nThe AI doesn't make decisions about you: Steyn checks and adjusts every plan before you get to see it. Anthropic processes the data as a processor and, under its business terms, does not use it to train AI models. Anthropic is based in the United States; the transfer takes place on the basis of appropriate safeguards, such as the European Commission's standard contractual clauses.\n\nYou can withdraw your consent at any time. Just get in touch; Steyn will then create your plan entirely himself.",
                    ],
                    [
                        'title' => 'Why?',
                        'body' => 'To contact you, schedule appointments, provide your coaching, track your progress and run Refer a friend. You only receive newsletters and offers if you choose to.',
                    ],
                    [
                        'title' => 'How long do we keep your data?',
                        'body' => 'For as long as your account exists or as long as needed for your coaching. Contact requests that don\'t lead to anything are deleted after 12 months at the latest. Legal retention obligations (such as for invoices) still apply.',
                    ],
                    [
                        'title' => 'Sharing with others',
                        'body' => 'We never sell your data. We only share it with parties needed to make the website and your coaching work (such as the hosting, the AI service above and Steyn\'s calendar app), under appropriate agreements.',
                    ],
                    [
                        'title' => 'Security',
                        'body' => 'Passwords are stored encrypted and your session is protected with a secure cookie. Only Steyn has access to your coaching data and intake.',
                    ],
                    [
                        'title' => 'Your rights',
                        'body' => 'You can always view and update your data in your profile, and delete your account with all related data yourself. For other requests (such as a copy of your data or withdrawing consent), you can contact us. Don\'t agree with how we handle your data? Then you can file a complaint with the Dutch Data Protection Authority (Autoriteit Persoonsgegevens).',
                    ],
                    [
                        'title' => 'Cookies',
                        'body' => 'We only use functional cookies: to keep you logged in, to remember your language and to keep track of whose invitation brought you here. No tracking or advertising cookies are used.',
                    ],
                ],
            ],
            'seo' => ['title' => 'Privacy policy', 'description' => 'How SteynPT handles your personal data.'],
        ],
    ];

    public static function slug(string $slug): string
    {
        return self::PREFIX.$slug;
    }

    public static function isNeutral(string $slug, string $section, string $key, array $field): bool
    {
        return in_array($field['kind'], self::NEUTRAL_KINDS, true) || in_array("{$slug}.{$section}.{$key}", self::NEUTRAL_KEYS, true);
    }

    /** Velden van een item die vertaald worden (zonder prijs en aan/uit). */
    private static function translatableSubs(array $field): array
    {
        return array_filter($field['fields'], fn (array $sub) => ! in_array($sub['kind'], self::NEUTRAL_KINDS, true));
    }

    private static function hasNeutralSubs(array $field): bool
    {
        return count(self::translatableSubs($field)) !== count($field['fields']);
    }

    /** Alleen de vertaalde velden van een item; wat ontbreekt komt uit $fallback (het Nederlandse item). */
    private static function pick(array $item, array $subs, array $fallback): array
    {
        $out = [];
        foreach ($subs as $k => $sub) {
            $out[$k] = $item[$k] ?? $fallback[$k] ?? ($sub['kind'] === 'list' ? [] : '');
        }

        return $out;
    }

    /**
     * De Engelse paginadefinitie: zelfde opbouw, Engelse standaardteksten, zonder de gedeelde velden.
     * $nlValues zijn de Nederlandse teksten van deze pagina (Values::resolvePage), voor het aantal pakketten.
     */
    public static function page(array $nlPage, array $nlValues): array
    {
        $texts = self::PAGES[$nlPage['slug']] ?? [];
        $page = $nlPage;
        $page['slug'] = self::slug($nlPage['slug']);
        $page['title'] = $nlPage['title'].' (Engels)';
        $page['description'] = trim($nlPage['description'].' Prijzen, links en het adres pas je aan in de Nederlandse versie.');
        $page['path'] = $nlPage['path'] === null ? null : Locale::path($nlPage['path'], 'en');
        $page['sections'] = [];
        foreach ($nlPage['sections'] as $s => $section) {
            $fields = [];
            foreach ($section['fields'] as $f => $field) {
                if (self::isNeutral($nlPage['slug'], $s, $f, $field)) {
                    continue;
                }
                $default = $texts[$s][$f] ?? $field['default'];
                if ($field['kind'] === 'items') {
                    $subs = self::translatableSubs($field);
                    if (self::hasNeutralSubs($field)) {
                        // Pakketten met een prijs: het aantal (en de prijs) komt uit het Nederlands.
                        $nlItems = $nlValues[$s][$f] ?? $field['default'];
                        $default = array_map(fn (int $i) => self::pick($default[$i] ?? [], $subs, $nlItems[$i]), array_keys($nlItems));
                        $field['fixed'] = true;
                        $field['hint'] = trim(($field['hint'] ?? '').' Het aantal pakketten en de prijzen stel je in bij de Nederlandse tekst.');
                    } else {
                        $default = array_map(fn (array $item) => self::pick($item, $subs, []), $default);
                    }
                    $field['fields'] = $subs;
                }
                $field['default'] = $default;
                $fields[$f] = $field;
            }
            if ($fields) {
                $page['sections'][$s] = ['title' => $section['title'], 'hint' => $section['hint'], 'fields' => $fields];
            }
        }

        return $page;
    }

    /** De Engelse teksten zoals de site ze gebruikt: met de gedeelde velden uit het Nederlands. */
    public static function merge(array $nlPage, array $nlValues, array $enValues): array
    {
        $out = $nlValues;
        foreach ($nlPage['sections'] as $s => $section) {
            foreach ($section['fields'] as $f => $field) {
                if (! array_key_exists($f, $enValues[$s] ?? [])) {
                    continue;
                }
                $en = $enValues[$s][$f];
                if ($field['kind'] === 'items' && self::hasNeutralSubs($field)) {
                    $out[$s][$f] = array_map(
                        fn (int $i) => array_replace($nlValues[$s][$f][$i], array_intersect_key($en[$i] ?? [], $nlValues[$s][$f][$i])),
                        array_keys($nlValues[$s][$f]),
                    );
                } else {
                    $out[$s][$f] = $en;
                }
            }
        }

        return $out;
    }
}
