<?php

namespace Alura\Pdo\Infrastructure\Repository;

use Alura\Pdo\Domain\Model\Student;
use Alura\Pdo\Domain\Repository\StudentRepository;
use PDO;

class PdoStudentRepository implements StudentRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  private function insert(Student $student): bool
  {
    $insertQuery = 'INSERT INTO students (name, birth_date) VALUES (:name, :birth_date);';
    $statement = $this->connection->prepare($insertQuery);

    $success = $statement->execute([
      ':name' => $student->name(),
      ':birth_date' => $student->birthDate()->format(format: 'Y-m-d'),
    ]);

    if ($success) {
      $student->defineId($this->connection->lastInsertId());
    }

    return $success;
  }

  public function save(Student $student): bool
  {
    if ($student->id() === null) {
      return $this->insert($student);
    }

    return $this->update($student);
  }

  public function allStudents(): array
  {
    $sqlQuery = 'SELECT * FROM students;';
    $statement = $this->connection->query($sqlQuery);

    return $this->hydrateStudentList($statement);
  }

  public function update(Student $student): bool
  {
    $updateQuery = 'UPDATE students SET name = :name, birth_date = :birth_date WHERE id = :id;';
    $statement = $this->connection->prepare($updateQuery);
    $statement->bindValue(':name', $student->name());
    $statement->bindValue(':birth_date', $student->birthDate()->format(format: 'Y-m-d'));
    $statement->bindValue(':id', $student->id(), PDO::PARAM_INT);

    return $statement->execute();
  }

  public function studentsBirthAt(\DateTimeInterface $birthDate): array
  {
    $sqlQuery = 'SELECT * FROM students WHERE birth_date = ?;';
    $statement = $this->connection->query($sqlQuery);
    $statement->bindValue(2, $birthDate->format('Y-m-d'));
    $statement->execute();

    return $this->hydrateStudentList($statement);
  }

  private function hydrateStudentList(\PDOStatement $statement): array
  {
    $studentDataList = $statement->fetchAll();
    $studentList = [];

    foreach ($studentDataList as $studentData) {
      $studentList[] = new Student(
        $studentData['id'],
        $studentData['name'],
        new \DateTimeImmutable($studentData['birth_date'])
      );
    }

    return $studentList;
  }

  public function remove(Student $student): bool
  {
    $statement = $this->connection->prepare("DELETE FROM students WHERE id = ?;");
    $statement->bindValue(1, $student->id(), PDO::PARAM_INT);

    return $statement->execute();
  }
}
