<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Exception\ForbiddenException;
use App\Interface\Controller;

class AboutUsController implements Controller
{
    public static function index(): void
    {
        if (!SessionAuth::hasAuthorizedSession()) {
            header('Location: ' . REDIRECT_PATH . 'login');
            exit();        
        }

        $memberData = [
            [
                'name' => 'Marl Aguiluz M. Aquino',
                'image' => 'aquino.png',
                'roles' => ['Database Designer', 'Database Analyst'],
                'bio' => 'I am Marl Aguiluz, a 21 year old man born on December 3, 2004. 
                    I am a third-year BSIT student at Rizal Technological University, Boni Campus. 
                    I enjoy spending my free time gaming, listening to music, and learning about history. 
                    My goal is to become an analyst and work in a well known tech company, either local or international, 
                    in where I can grow and apply my skills. My biggest motivation comes from my family, my supportive 
                    girlfriend, and my beloved cats, who inspire me to work harder every day. I am determined to build 
                    a successful future and become an upcoming reliable professional in the tech industry.'
            ],
            [
                'name' => 'Peter Marcus S. Dela Cruz',
                'image' => 'dela-cruz.png',
                'roles' => ['UI/UX Designer'],
                'bio' => 'I am Peter, a 20-year-old born on October 9, 2005. I am a third-year BSIT student 
                    passionate about different things that revolved to the topic of technology. I am familiar 
                    to different things such as web development, networking, animation etc., But I am open to learn 
                    and explore new things that can make an impact to my records in the future. With the support from my 
                    parents and family, It boosts me to push and explore more my knowledge in technology and increase my skills 
                    that I have and will have in the future.'
            ],
            [
                'name' => 'Edzel D. Funclara',
                'image' => 'funclara.jpg',
                'roles' => ['UI/UX Designer'],
                'bio' => 'I am Edzel Dy Funclara, a 20-year-old man born on September 3, 2005. I am a third-year 
                    BSIT student at Rizal Technological University, Boni Campus. I enjoy spending my free time reading 
                    novels, gaming, practicing my drawing, and watching different kinds of content that help me relax 
                    and learn new things. My goal is to become an IT Specialist and work in a well-known company where 
                    I can grow and apply my skills. I also dream of becoming a pharmacist someday because I want to help 
                    people and be part of their recovery. I am determined to build a successful future 
                    and become a skilled and dedicated professional in both technology and healthcare.'
            ],
            [
                'name' => 'Jeroen Gil S. Paghunasan',
                'image' => 'paghunasan.jpg',
                'roles' => ['Frontend Developer'],
                'bio' => 'I am Jeroen Gil, a 21-year-old born on November 13, 2004. I am a third-year BSIT student 
                    passionate about technology, especially web development, cybersecurity, and data analysis. I enjoy 
                    exploring new tech trends, gaming, and learning skills that help me grow as a future professional. 
                    My goal is to work in a reputable tech company, either local or international, where I can apply my 
                    knowledge and continue improving. I stay motivated by my ambitions and the people who support me, 
                    pushing me to work harder and build a strong future in the tech industry.'
            ],
            [
                'name' => 'Kurt O. Secretario',
                'image' => 'multo.jpg',
                'roles' => ['UI/UX Designer', 'Fullstack Developer', 'Database Designer', 'Database Analyst'],
                'bio' => 'I am Kurt, a 20-year-old born on January 04, 2005. I am a third-year BSIT student 
                    passionate about technology and its various facets, including web development, UI/UX design, and database management. 
                    I enjoy exploring new technologies and enhancing my skills to stay updated in the ever-evolving tech landscape. 
                    My goal is to work in a reputable tech company where I can apply my knowledge and continue to grow professionally. 
                    I am motivated by my aspirations and the support of my family and friends, which drives me to work diligently towards building 
                    a successful future in the tech industry.'
            ]
        ];

        require_once VIEW_PATH . 'about-us.php';
    }
}