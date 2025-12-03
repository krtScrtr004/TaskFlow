<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Exception\ForbiddenException;
use App\Interface\Controller;
use App\Middleware\Csrf;

class AboutUsController implements Controller
{
    public static function index(): void
    {
    }

    /**
     * Renders the "About Us" page for authenticated users.
     *
     * This method handles page access and prepares team member data for the view:
     * - Verifies an authorized session using SessionAuth::hasAuthorizedSession().
     * - Redirects to the login route (REDIRECT_PATH . 'login') and exits when the session is not authorized.
     * - Builds an array ($memberData) of team member metadata where each member contains:
     *     - name: string Full name of the member
     *     - image: string Filename of the member's profile image
     *     - roles: string[] List of roles/responsibilities for the member
     *     - bio: string Member biography/description
     * - Includes the about-us view (VIEW_PATH . 'about-us.php') to render the page.
     *
     * Notes:
     * - The method sets headers and terminates execution on unauthorized access; it performs no return.
     * - The $memberData structure is intended to be consumed by the included view.
     *
     * @var array $memberData Associative array of team members with following keys per entry:
     *      - name: string Member's full name
     *      - image: string Image filename or relative path
     *      - roles: string[] Roles assigned to the member
     *      - bio: string Biographical text for the member
     *
     * @return void
     * @uses SessionAuth::hasAuthorizedSession() Check for authenticated session
     * @uses REDIRECT_PATH Constant prefix used when redirecting to login
     * @uses VIEW_PATH Path used to include the about-us view file
     */
    public static function viewAboutUs(): void
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

    /**
     * Renders the Terms and Conditions view.
     *
     * This static method includes the 'terms-and-conditions.html' view from the configured VIEW_PATH.
     * It executes the included file in the current scope and is intended to output the terms and
     * conditions HTML directly to the response.
     *
     * Behavior:
     * - Resolves the view file path using the VIEW_PATH constant and the filename 'terms-and-conditions.html'.
     * - Uses require_once to include the file, preventing multiple inclusions.
     * - Produces no return value; side effects may include direct output and variable scope exposure.
     *
     * Security & errors:
     * - VIEW_PATH must be defined and point to a trusted directory to avoid including unintended files.
     * - If the view file is missing or not readable, inclusion will trigger a fatal error (Error).
     *
     * @return void
     * @throws \Error If the view file cannot be included (e.g., missing or unreadable)
     */
    public static function viewTermsAndConditions(): void
    {
        require_once VIEW_PATH . 'terms-and-conditions.php';
    }
}