<?php
namespace ScriptFUSION\Porter\Transform\Mapping;

use Amp\Promise;
use Amp\Success;
use ScriptFUSION\Mapper\CollectionMapper;
use ScriptFUSION\Mapper\Mapping;
use ScriptFUSION\Porter\Collection\RecordCollection;
use ScriptFUSION\Porter\PorterAware;
use ScriptFUSION\Porter\PorterAwareTrait;
use ScriptFUSION\Porter\Transform\AsyncTransformer;
use ScriptFUSION\Porter\Transform\Mapping\Collection\CountableMappedRecords;
use ScriptFUSION\Porter\Transform\Mapping\Collection\MappedRecords;
use ScriptFUSION\Porter\Transform\Mapping\Mapper\PorterMapper;
use ScriptFUSION\Porter\Transform\Transformer;

class MappingTransformer implements Transformer, AsyncTransformer, PorterAware
{
    use PorterAwareTrait;

    /**
     * @var CollectionMapper
     */
    private $mapper;

    /**
     * @var Mapping
     */
    private $mapping;

    public function __construct(Mapping $mapping)
    {
        $this->mapping = $mapping;
    }

    public function __clone()
    {
        $this->mapping = clone $this->mapping;
        // Cloning the mapper doesn't serve any useful purpose because it is stateless.
    }

    public function transform(RecordCollection $records, $context): RecordCollection
    {
        return $this->createMappedRecords(
            $this->getOrCreateMapper()->mapCollection($records, $this->mapping, $context),
            $records,
            $this->mapping
        );
    }

    public function transformAsync(array $record, $context): Promise
    {
        return new Success($this->getOrCreateMapper()->map($record, $this->mapping, $context));
    }

    private function createMappedRecords(\Iterator $records, RecordCollection $previous, Mapping $mapping)
    {
        // Copy count of previous collection because a mapping operation cannot modify the number of records.
        if ($previous instanceof \Countable) {
            return new CountableMappedRecords($records, count($previous), $previous, $mapping);
        }

        return new MappedRecords($records, $previous, $mapping);
    }

    private function getOrCreateMapper(): CollectionMapper
    {
        return $this->mapper ?: $this->mapper = new PorterMapper($this->getPorter());
    }

    /**
     * @param CollectionMapper $mapper
     *
     * @return $this
     */
    public function setMapper(CollectionMapper $mapper): self
    {
        $this->mapper = $mapper;

        return $this;
    }
}
