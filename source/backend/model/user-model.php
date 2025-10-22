<?php

namespace App\Model;

use App\Abstract\Model;
use App\Core\Connection;
use App\Container\JobTitleContainer;
use App\Core\UUID;
use App\Entity\User;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Exception\DatabaseException;
use DateTime;
use InvalidArgumentException;
use PDOException;

class UserModel extends Model
{


    public static function findByEmail(string $email): ?array
    {
        try {
            $query = "SELECT * FROM `user` WHERE email = :email LIMIT 1";
            $statement = Connection::getInstance()->prepare($query);
            $statement->execute([':email' => $email]);
            $result = $statement->fetch();

            return $result ?: null;
        } catch (PDOException $th) {
            throw new DatabaseException($th->getMessage());
        }
    }

    public static function create(mixed $user): void
    {
        if (!($user instanceof User)) {
            throw new InvalidArgumentException('Expected instance of User');
        }
        
        $uuid = UUID::get();
        $firstName = trim($user->getFirstName()) ?: null;
        $middleName = trim($user->getMiddleName()) ?: null;
        $lastName = trim($user->getLastName()) ?: null;
        $gender = $user->getGender() ? $user->getGender()->value : null;
        $birthDate = $user->getBirthDate() ? formatDateTime($user->getBirthDate()) : null;
        $role = $user->getRole() ? $user->getRole()->value : null;
        $jobTitles = $user->getJobTitles() ? $user->getJobTitles()->toArray() : [];
        $contactNumber = trim($user->getContactNumber()) ?: null;
        $email = trim($user->getEmail()) ?: null;
        $bio = trim($user->getBio()) ?: null;
        $profileLink = trim($user->getProfileLink()) ?: null;
        $password = $user->getPassword() ?: null;

        $conn = Connection::getInstance();
        try {
            $conn->beginTransaction();

            // Insert User Data
            $userQuery = "
                INSERT INTO `user` (
                    publicId, 
                    firstName, 
                    middleName, 
                    lastName, 
                    gender, 
                    birthDate, 
                    role, 
                    contactNumber, 
                    email, 
                    bio, 
                    profileLink, 
                    password
                ) VALUES (
                    :publicId, 
                    :firstName, 
                    :middleName, 
                    :lastName, 
                    :gender, 
                    :birthDate, 
                    :role, 
                    :contactNumber, 
                    :email, 
                    :bio, 
                    :profileLink, 
                    :password
                )
            ";
            $statement = $conn->prepare($userQuery);
            $statement->execute([
                ':publicId' => UUID::toBinary($uuid),
                ':firstName' => $firstName,
                ':middleName' => $middleName,
                ':lastName' => $lastName,
                ':gender' => $gender,
                ':birthDate' => $birthDate,
                ':role' => $role,
                ':contactNumber' => $contactNumber,
                ':email' => $email,
                ':bio' => $bio,
                ':profileLink' => $profileLink,
                ':password' => password_hash($password, PASSWORD_ARGON2ID)
            ]);

            // Insert Job Titles, if any
            if (!empty($jobTitles)) {
                $userId = $conn->lastInsertId();
                $jobTitleQuery = "
                    INSERT INTO `userJobTitle` (userId, title)
                    VALUES (:userId, :title)
                ";
                $jobTitleStatement = $conn->prepare($jobTitleQuery);
                foreach ($jobTitles as $title) {
                    $jobTitleStatement->execute([
                        ':userId' => $userId,
                        ':title' => $title
                    ]);
                }
            }

            $conn->commit();
        } catch (\Throwable $th) {
            $conn->rollBack();
            throw new DatabaseException($th->getMessage());
        }
    }















    // 

    public function get()
    {
        return [];
    }


    public function save(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }


    public static function all(): array
    {
        // $users = [
        //     new User(
        //         random_int(1, 1000),
        //         uniqid(),
        //         'Alice',
        //         'B.',
        //         'Smith',
        //         Gender::FEMALE,
        //         new DateTime('1990-05-15'),
        //         Role::WORKER,
        //         new JobTitleContainer(['Software Engineer', 'Team Lead', 'Architect']),
        //         '123-456-7890',
        //         'alice@example.com',
        //         'Experienced developer',
        //         null,
        //         new DateTime('2020-01-10')
        //     ),
        //     new User(
        //         random_int(1, 1000),
        //         uniqid(),
        //         'Bob',
        //         'C.',
        //         'Johnson',
        //         Gender::MALE,
        //         new DateTime('1985-08-22'),
        //         Role::WORKER,
        //         new JobTitleContainer(['Designer', 'Illustrator', 'Photographer']),
        //         '987-654-3210',
        //         'bob@example.com',
        //         'Skilled designer',
        //         null,
        //         new DateTime('2019-03-25')
        //     )
        // ];
        // return $users;
        return [];
    }

    public static function find($id): ?self
    {
        return null;
    }

}