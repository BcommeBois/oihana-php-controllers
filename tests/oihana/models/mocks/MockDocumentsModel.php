<?php

namespace tests\oihana\models\mocks;

use Generator;
use oihana\models\enums\ModelParam;
use oihana\models\interfaces\DocumentsModel;
use org\schema\constants\Schema;

/**
 * Mock implementation of a Documents model for testing purposes.
 *
 * This mock provides a simple in-memory storage for documents
 * and implements the DocumentsModel interface needed for testing
 * the AlterGetDocumentPropertyTrait and other document-related functionality.
 *
 * Features:
 * - In-memory document storage
 * - Full CRUD operations
 * - Support for custom key/value queries
 * - Sequential array handling
 * - Utility methods for test setup
 *
 * @package oihana\models\mocks
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class MockDocumentsModel implements DocumentsModel
{
    /**
     * In-memory storage for documents
     * @var array
     */
    protected array $documents = [];

    /**
     * Auto-increment ID for new documents without an ID
     * @var int
     */
    protected int $autoIncrementId = 1;

    /**
     * Add a document to the mock storage.
     *
     * @param array $document The document to add
     * @return static
     */
    public function addDocument( array $document ): static
    {
        // Auto-assign ID if not present
        if (!isset($document['id']))
        {
            $document['id'] = $this->autoIncrementId++;
        }

        $this->documents[] = $document;
        return $this;
    }

    /**
     * Add multiple documents to the mock storage.
     *
     * @param array $documents Array of documents to add
     * @return static
     */
    public function addDocuments(array $documents): static
    {
        foreach ($documents as $document) {
            $this->addDocument($document);
        }
        return $this;
    }

    /**
     * Clear all documents from the mock storage.
     *
     * @return static
     */
    public function clear(): static
    {
        $this->documents = [];
        $this->autoIncrementId = 1;
        return $this;
    }

    /**
     * Get all documents (utility method).
     *
     * @return array All stored documents
     */
    public function getAll(): array
    {
        return $this->documents;
    }

    /**
     * Set documents directly (utility method for testing).
     *
     * @param array $documents Documents to set
     * @return static
     */
    public function setDocuments(array $documents): static
    {
        $this->documents = $documents;
        return $this;
    }

    // ========== DocumentsModel Interface Implementation ==========

    /**
     * Count the number of documents in storage.
     *
     * Supports filtering by key/value:
     * - ['key' => 'status', 'value' => 'active'] counts only active documents
     *
     * @param array $init Optional parameters:
     *   - ModelParam::KEY: Property key to filter by
     *   - ModelParam::VALUE: Value to match
     * @return int The number of matching documents
     */
    public function count(array $init = []): int
    {
        if (empty($init)) {
            return count($this->documents);
        }

        $key   = $init[ModelParam::KEY   ] ?? null ;
        $value = $init[ModelParam::VALUE ] ?? null ;

        if ($key === null)
        {
            return count($this->documents);
        }

        $count = 0 ;
        foreach ($this->documents as $document)
        {
            if (isset($document[$key]) && $document[$key] === $value) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Delete a document from storage.
     *
     * @param array $init Parameters:
     *   - ModelParam::KEY: Property key to search by (default: 'id')
     *   - ModelParam::VALUE: Value to match
     * @return array|object|null The deleted document or null if not found
     */
    public function delete(array $init = []): null|array|object
    {
        $key = $init[ModelParam::KEY] ?? 'id';
        $value = $init[ModelParam::VALUE] ?? null;

        if ($value === null) {
            return null;
        }

        foreach ($this->documents as $index => $document) {
            if (isset($document[$key]) && $document[$key] === $value) {
                $deleted = $this->documents[$index];
                unset($this->documents[$index]);
                $this->documents = array_values($this->documents);
                return $deleted;
            }
        }

        return null;
    }

    /**
     * Check if a document exists.
     *
     * @param array $init Parameters:
     *   - ModelParam::KEY: Property key to check (default: 'id')
     *   - ModelParam::VALUE: Value to search for
     * @return bool True if document exists, false otherwise
     */
    public function exist(array $init = []): bool
    {
        return $this->get($init) !== null;
    }

    /**
     * Retrieve a document by key and value.
     *
     * @param array $init Parameters:
     *   - ModelParam::KEY: Property key to search by (default: 'id')
     *   - ModelParam::VALUE: Value to search for
     * @return mixed The found document or null if not found
     */
    public function get( array $init = [] ) :mixed
    {
        $key   = $init[ ModelParam::KEY   ] ?? 'id' ;
        $value = $init[ ModelParam::VALUE ] ?? null ;

        if ($value === null || $value === '')
        {
            return null;
        }

        return array_find
        (
            $this->documents ,
            fn( $document ) => isset( $document[ $key ] ) && $document[ $key ] === $value
        );
    }

    /**
     * Insert a new document into storage.
     *
     * @param array $init Parameters:
     *   - 'document': The document to insert
     *   - Or pass document fields directly in $init
     * @return mixed The inserted document
     */
    public function insert(array $init = []): mixed
    {
        $document = $init['document'] ?? $init;

        // Auto-assign ID if not present
        if (!isset($document['id'])) {
            $document['id'] = $this->autoIncrementId++;
        }

        $this->documents[] = $document;
        return $document;
    }

    /**
     * Get the last document in storage.
     *
     * Supports filtering by key/value like get().
     *
     * @param array $init Optional parameters:
     *   - ModelParam::KEY: Property key to filter by
     *   - ModelParam::VALUE: Value to match
     * @return mixed The last matching document or null
     */
    public function last(array $init = []): mixed
    {
        if (empty($this->documents)) {
            return null;
        }

        if (empty($init)) {
            return end($this->documents);
        }

        $key = $init[ModelParam::KEY] ?? null;
        $value = $init[ModelParam::VALUE] ?? null;

        if ($key === null) {
            return end($this->documents);
        }

        // Find last matching document
        $matches = array_filter(
            $this->documents,
            fn($doc) => isset($doc[$key]) && $doc[$key] === $value
        );

        return empty($matches) ? null : end($matches);
    }

    /**
     * List all documents in storage.
     *
     * Supports filtering, limiting, and sorting:
     * - ['key' => 'status', 'value' => 'active'] returns only active documents
     * - ['limit' => 10] returns first 10 documents
     * - ['offset' => 5] skips first 5 documents
     *
     * @param array $init Optional parameters:
     *   - ModelParam::KEY: Property key to filter by
     *   - ModelParam::VALUE: Value to match
     *   - 'limit': Maximum number of documents to return
     *   - 'offset': Number of documents to skip
     * @return array All matching documents
     */
    public function list(array $init = []): array
    {
        $documents = $this->documents;

        // Filter by key/value if provided
        $key = $init[ModelParam::KEY] ?? null;
        $value = $init[ModelParam::VALUE] ?? null;

        if ($key !== null && $value !== null) {
            $documents = array_filter(
                $documents,
                fn($doc) => isset($doc[$key]) && $doc[$key] === $value
            );
        }

        // Apply offset
        $offset = $init['offset'] ?? 0;
        if ($offset > 0) {
            $documents = array_slice($documents, $offset);
        }

        // Apply limit
        $limit = $init['limit'] ?? null;
        if ($limit !== null && $limit > 0) {
            $documents = array_slice($documents, 0, $limit);
        }

        return array_values($documents);
    }

    /**
     * Replace an existing document.
     *
     * @param array $init Parameters:
     *   - ModelParam::KEY: Property key to search by (default: 'id')
     *   - ModelParam::VALUE: Value to search for
     *   - 'document': The new document data
     * @return mixed The replaced document or null if not found
     */
    public function replace(array $init = []): mixed
    {
        $key = $init[ModelParam::KEY] ?? 'id';
        $value = $init[ModelParam::VALUE] ?? null;
        $document = $init['document'] ?? [];

        if ($value === null) {
            return null;
        }

        foreach ($this->documents as $index => $doc)
        {
            if (isset($doc[$key]) && $doc[$key] === $value) {
                // Preserve the search key in the new document
                if (!isset($document[$key])) {
                    $document[$key] = $value;
                }
                $this->documents[$index] = $document;
                return $this->documents[$index];
            }
        }

        return null;
    }

    /**
     * Stream documents from storage.
     *
     * Provides a generator that yields each document one by one.
     * Supports optional filtering, offset, and limit (same as `list()`).
     *
     * @param array<string, mixed> $init Optional parameters:
     *   - ModelParam::KEY: Property key to filter by
     *   - ModelParam::VALUE: Value to match
     *   - 'offset': Number of documents to skip
     *   - 'limit': Maximum number of documents to yield
     * @return Generator<array<string, mixed>>
     */
    public function stream( array $init = [] ): Generator
    {
        $documents = $this->list( $init ) ;
        foreach ($documents as $document) {
            yield $document;
        }
    }

    /**
     * Update an existing document (merge with existing data).
     *
     * @param array $init Parameters:
     *   - ModelParam::KEY: Property key to search by (default: 'id')
     *   - ModelParam::VALUE: Value to search for
     *   - 'data': The data to merge/update
     *   - Or pass update fields directly in $init
     * @return mixed The updated document or null if not found
     */
    public function update(array $init = []): mixed
    {
        $key   = $init[ModelParam::KEY] ?? 'id';
        $value = $init[ModelParam::VALUE] ?? null;
        $data  = $init['data'] ?? $init;

        if ($value === null) {
            return null;
        }

        // Remove meta keys from data
        unset($data[ModelParam::KEY], $data[ModelParam::VALUE], $data['data']);

        foreach ($this->documents as $index => $document) {
            if (isset($document[$key]) && $document[$key] === $value) {
                $this->documents[$index] = array_merge($document, $data);
                return $this->documents[$index];
            }
        }

        return null;
    }

    /**
     * Update the date property (default: Schema::MODIFIED) of a document.
     *
     * @param array  $init Parameters:
     *   - ModelParam::KEY: Property key to search by (default: 'id')
     *   - ModelParam::VALUE: Value to search for
     * @param string $property The property name to update (default: Schema::MODIFIED)
     * @return mixed The updated document or null if not found
     */
    public function updateDate(array $init = [], string $property = Schema::MODIFIED): mixed
    {
        $key   = $init[ ModelParam::KEY   ] ?? 'id' ;
        $value = $init[ ModelParam::VALUE ] ?? null ;

        if ($value === null) {
            return null;
        }

        foreach ($this->documents as $index => $document)
        {
            if (isset($document[$key]) && $document[$key] === $value)
            {
                $this->documents[$index][$property] = date('c'); // ISO 8601 format
                return $this->documents[$index];
            }
        }

        return null;
    }

    /**
     * Insert or update a document (upsert operation).
     *
     * If a document with the given key/value exists, update it.
     * Otherwise, insert a new document.
     *
     * @param array $init Parameters:
     *   - ModelParam::KEY: Property key to search by (default: 'id')
     *   - ModelParam::VALUE: Value to search for
     *   - 'document': The document data
     * @return mixed The upserted document
     */
    public function upsert(array $init = []): mixed
    {
        $key = $init[ModelParam::KEY] ?? 'id';
        $value = $init[ModelParam::VALUE] ?? null;
        $document = $init['document'] ?? $init;

        if ($value === null) {
            return $this->insert(['document' => $document]);
        }

        $existing = $this->get
        ([
            ModelParam::KEY => $key,
            ModelParam::VALUE => $value
        ]);

        if ($existing !== null)
        {
            return $this->update
            ([
                ModelParam::KEY => $key,
                ModelParam::VALUE => $value,
                'data' => $document
            ]);
        }

        // Ensure the key/value is in the document
        if (!isset($document[$key])) {
            $document[$key] = $value;
        }

        return $this->insert(['document' => $document]);
    }

    /**
     * Truncate (remove all documents) from storage.
     *
     * @param array $init Optional parameters (not used in mock)
     * @return mixed Number of documents removed
     */
    public function truncate(array $init = []): mixed
    {
        $count = count($this->documents);
        $this->documents = [];
        $this->autoIncrementId = 1;
        return $count;
    }
}