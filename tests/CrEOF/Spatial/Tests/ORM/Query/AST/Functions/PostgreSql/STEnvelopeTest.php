<?php
/**
 * Copyright (C) 2020 Alexandre Tranchant
 * Copyright (C) 2015 Derek J. Lambert
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace CrEOF\Spatial\Tests\ORM\Query\AST\Functions\PostgreSql;

use CrEOF\Spatial\Exception\InvalidValueException;
use CrEOF\Spatial\Exception\UnsupportedPlatformException;
use CrEOF\Spatial\PHP\Types\Geometry\LineString;
use CrEOF\Spatial\PHP\Types\Geometry\Point;
use CrEOF\Spatial\PHP\Types\Geometry\Polygon;
use CrEOF\Spatial\Tests\Fixtures\PolygonEntity;
use CrEOF\Spatial\Tests\OrmTestCase;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * ST_Envelope DQL function tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @coversDefaultClass
 */
class STEnvelopeTest extends OrmTestCase
{
    /**
     * Setup the function type test.
     *
     * @throws DBALException                when connection failed
     * @throws ORMException                 when cache is not set
     * @throws UnsupportedPlatformException when platform is unsupported
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POLYGON_ENTITY);
        $this->supportsPlatform('postgresql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @throws DBALException                when connection failed
     * @throws ORMException                 when cache is not set
     * @throws UnsupportedPlatformException when platform is unsupported
     * @throws MappingException             when mapping
     * @throws OptimisticLockException      when clear fails
     * @throws InvalidValueException        when geometries are not valid
     *
     * @group geometry
     */
    public function testSelectSTEnvelope()
    {
        $entity1 = new PolygonEntity();
        $rings1 = [
            new LineString([
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0),
            ]),
        ];

        $entity1->setPolygon(new Polygon($rings1));
        $this->getEntityManager()->persist($entity1);

        $entity2 = new PolygonEntity();
        $rings2 = [
            new LineString([
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0),
            ]),
            new LineString([
                new Point(5, 5),
                new Point(7, 5),
                new Point(7, 7),
                new Point(5, 7),
                new Point(5, 5),
            ]),
        ];

        $entity2->setPolygon(new Polygon($rings2));
        $this->getEntityManager()->persist($entity2);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery('SELECT ST_AsText(ST_Envelope(p.polygon)) FROM CrEOF\Spatial\Tests\Fixtures\PolygonEntity p');
        $result = $query->getResult();

        $this->assertEquals('POLYGON((0 0,0 10,10 10,10 0,0 0))', $result[0][1]);
        $this->assertEquals('POLYGON((0 0,0 10,10 10,10 0,0 0))', $result[1][1]);
    }

    /**
     * Test a DQL containing function to test in the predicate.
     *
     * @throws DBALException                when connection failed
     * @throws ORMException                 when cache is not set
     * @throws UnsupportedPlatformException when platform is unsupported
     * @throws MappingException             when mapping
     * @throws OptimisticLockException      when clear fails
     * @throws InvalidValueException        when geometries are not valid
     *
     * @group geometry
     */
    public function testSTEnvelopeWhereParameter()
    {
        $entity1 = new PolygonEntity();
        $rings1 = [
            new LineString([
                new Point(0, 0),
                new Point(10, 0),
                new Point(10, 10),
                new Point(0, 10),
                new Point(0, 0),
            ]),
            new LineString([
                new Point(5, 5),
                new Point(7, 5),
                new Point(7, 7),
                new Point(5, 7),
                new Point(5, 5),
            ]),
        ];

        $entity1->setPolygon(new Polygon($rings1));
        $this->getEntityManager()->persist($entity1);

        $entity2 = new PolygonEntity();
        $rings2 = [
            new LineString([
                new Point(5, 5),
                new Point(7, 5),
                new Point(7, 7),
                new Point(5, 7),
                new Point(5, 5),
            ]),
        ];

        $entity2->setPolygon(new Polygon($rings2));
        $this->getEntityManager()->persist($entity2);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery('SELECT p FROM CrEOF\Spatial\Tests\Fixtures\PolygonEntity p WHERE ST_Envelope(p.polygon) = ST_GeomFromText(:p1)');

        $query->setParameter('p1', 'POLYGON((0 0,10 0,10 10,0 10,0 0))', 'string');

        $result = $query->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals($entity1, $result[0]);
    }
}
