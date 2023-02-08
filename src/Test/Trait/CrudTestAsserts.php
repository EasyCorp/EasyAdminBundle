<?php

declare( strict_types=1 );

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Trait;

trait CrudTestAsserts
{
	protected function assertIndexRecordCount(int $expectedIndexRecordCount): void
	{
		if ( 0 > $expectedIndexRecordCount) {
			throw new \InvalidArgumentException();
		}

		if ( 0 === $expectedIndexRecordCount) {
			static::assertSelectorTextSame('.no-results', 'No results found.');
		} else {
			static::assertSelectorTextSame('.list-pagination-counter strong', (string) $expectedIndexRecordCount);
		}
	}
}
