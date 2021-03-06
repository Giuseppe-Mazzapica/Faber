<?php namespace GM\Faber\Tests;

class FaberTest extends TestCase {

    private function getFaber( $id, $things = [ ] ) {
        return new \GM\Faber( $things, $id );
    }

    function testMagicToString() {
        $faber = $this->getFaber( 'foo' );
        assertEquals( "{$faber}", "GM\Faber foo" );
    }

    function testMagicCall() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'foo' ] = 'bar';
        assertEquals( $faber->getFoo(), "bar" );
    }

    function testMagicCallError() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'foo' ] = 'bar';
        assertInstanceOf( 'WP_Error', $faber->getBar() );
    }

    function testMacicSet() {
        $faber = $this->getFaber( 'foo' );
        $faber->foo = 'bar';
        assertEquals( $faber[ 'foo' ], 'bar' );
    }

    function testMacicGet() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'foo' ] = 'bar';
        assertEquals( $faber->foo, 'bar' );
    }

    function testSleepAndWakeUp() {
        $faber = $this->getFaber( 'test' );
        $faber[ 'foo' ] = function() {
            return new Stub;
        };
        $faber[ 'bar' ] = 'baz';
        $faber->protect( 'hello', function() {
            return 'Hello';
        } );
        $foo = $faber[ 'foo' ];
        $bar = $faber[ 'bar' ];
        $hello = $faber[ 'hello' ];
        $sleep = serialize( $faber );
        $wakeup = unserialize( $sleep );
        $foo2 = $wakeup[ 'foo' ];
        $bar2 = $wakeup[ 'bar' ];
        $hello2 = $wakeup[ 'hello' ];
        assertInstanceOf( 'GM\Faber\Tests\Stub', $foo );
        assertInstanceOf( 'Closure', $hello );
        assertTrue( $foo === $foo2 );
        assertTrue( $bar === $bar2 );
        assertTrue( $hello === $hello2 );
    }

    function testLoad() {
        $faber = $this->getFaber( 'foo' );
        $closure = function() {
            return new \WP_Error;
        };
        $data = [
            'foo'   => 'bar',
            'bar'   => 'baz',
            'error' => $closure
        ];
        $load = $faber->load( $data );
        assertEquals( $faber[ 'foo' ], 'bar' );
        assertEquals( $faber[ 'bar' ], 'baz' );
        assertInstanceOf( 'WP_Error', $faber[ 'error' ] );
        assertTrue( $load === $faber );
    }

    function testLoadFile() {
        $faber = $this->getFaber( 'foo' );
        $load = $faber->loadFile( FABERPATH . '/tests/helpers/array_example.php' );
        assertEquals( $faber[ 'foo' ], 'bar' );
        assertEquals( $faber[ 'bar' ], 'baz' );
        assertInstanceOf( 'WP_Error', $faber[ 'error' ] );
        assertTrue( $load === $faber );
    }

    function testLoadFileError() {
        $faber = $this->getFaber( 'foo' );
        $load = $faber->loadFile( FABERPATH . '/tests/helpers/i-do-not-exists.php' );
        assertInstanceOf( 'WP_Error', $load );
    }

    function testGetKey() {
        $faber = $this->getFaber( 'foo' );
        $key1 = $faber->getObjectKey( 'foo' );
        $key2 = $faber->getObjectKey( 'foo', [ 'foo' ] );
        $key3 = $faber->getObjectKey( 'foo', [ 'foo', 'bar' ] );
        $key4 = $faber->getObjectKey( 'foo' );
        $key5 = $faber->getObjectKey( 'foo', [ 'foo', 'bar' ] );
        assertNotEquals( $key1, $key2 );
        assertNotEquals( $key1, $key3 );
        assertNotEquals( $key2, $key3 );
        assertEquals( $key1, $key4 );
        assertEquals( $key3, $key5 );
    }

    function testAdd() {
        $faber = $this->getFaber( 'foo' );
        $closure = function() {
            return new \WP_Error;
        };
        $faber->add( 'foo', 'bar' );
        $faber->add( 'error', $closure );
        $add = $faber->add( 'foo', 'I never exists' );
        assertEquals( $faber[ 'foo' ], 'bar' );
        assertInstanceOf( 'WP_Error', $faber[ 'error' ] );
        assertTrue( $add === $faber );
    }

    function testProtect() {
        $faber = $this->getFaber( 'foo' );
        $closure = function() {
            return new \WP_Error;
        };
        $protect = $faber->protect( 'my_closure', $closure );
        assertEquals( $closure, $faber[ 'my_closure' ] );
        assertTrue( $protect === $faber );
    }

    function testProp() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'foo' ] = 'bar';
        $faber[ 'stub' ] = function() {
            return new Stub;
        };
        $closure = function() {
            return 'Ciao!';
        };
        $faber->protect( 'hello', $closure );
        assertEquals( 'bar', $faber->prop( 'foo' ) );
        assertEquals( $closure, $faber->prop( 'hello' ) );
        assertInstanceOf( 'WP_Error', $faber->prop( 'stub' ) );
        assertInstanceOf( 'WP_Error', $faber->prop( 'bar' ) );
    }

    function testGetWhenProp() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'foo' ] = 'bar';
        assertEquals( $faber->get( 'foo' ), 'bar' );
    }

    function testGetWhenFactory() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub2' ] = function( $c, $args = [ ] ) {
            $stub = new Stub;
            $stub->foo = $c[ 'foo' ];
            $stub->bar = $c[ 'bar' ];
            $stub->args = $args;
            $stub->sub_stub = $c[ 'stub' ];
            return $stub;
        };
        $faber[ 'stub' ] = function( $c, $args = [ ] ) {
            $stub = new Stub;
            $stub->foo = $c[ 'foo' ];
            $stub->bar = $c[ 'bar' ];
            $stub->args = $args;
            return $stub;
        };
        $faber[ 'foo' ] = 'bar';
        $faber[ 'bar' ] = 'baz';
        $stub = $faber->get( 'stub2', [ 'id' => 'stub2' ] );
        $stub_clone = $faber->get( 'stub2', [ 'id' => 'stub2' ] );
        $stub_alt = $faber->get( 'stub2', [ 'id' => 'stub_alt' ] );
        $stub_alt_clone = $faber->get( 'stub2', [ 'id' => 'stub_alt' ] );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $stub );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $stub_clone );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $stub_alt );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $stub->sub_stub );
        assertTrue( $stub->sub_stub === $faber->get( 'stub' ) );
        assertTrue( $stub->sub_stub !== $stub );
        assertEquals( 'stub2', $stub->args[ 'id' ] );
        assertEquals( 'stub_alt', $stub_alt->args[ 'id' ] );
        assertTrue( $stub_clone === $stub );
        assertTrue( $stub_alt !== $stub );
        assertTrue( $stub_alt_clone === $stub_alt );
    }

    function testGetWhenFactoryAndAssure() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub' ] = function() {
            return new Stub;
        };
        $a = $faber->get( 'stub', [ ], 'GM\Faber\Tests\Stub' );
        $b = $faber->get( 'stub', [ ], 'GM\Faber' );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $a );
        assertInstanceOf( 'WP_Error', $b );
    }

    function testGetWrongFactory() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub' ] = function() {
            return new Stub;
        };
        $a = $faber->get( 'foo' );
        assertInstanceOf( 'WP_Error', $a );
    }

    function testGetDemeterChain() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub' ] = function() {
            return new Stub;
        };
        assertSame( 'Result!', $faber->get( 'stub->getAltStub->getFooStub->getResult' ) );
    }

    function testMake() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub' ] = function() {
            return new Stub;
        };
        $a = $faber->make( 'stub' );
        $b = $faber->make( 'stub' );
        $c = $faber->make( 'stub' );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $a );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $b );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $c );
        assertTrue( $a !== $b );
        assertTrue( $a !== $c );
        assertTrue( $b !== $c );
    }

    function testMakeError() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub' ] = function() {
            return new Stub;
        };
        $a = $faber->make( 'foo' );
        assertInstanceOf( 'WP_Error', $a );
    }

    function testFreeze() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'foo' ] = 'bar';
        $faber[ 'bar' ] = 'baz';
        $faber[ 'baz' ] = 'foo';
        $faber->freeze( 'foo' );
        $freeze = $faber->freeze( 'bar' );
        $faber[ 'foo' ] = 'Sad man';
        $faber[ 'bar' ] = 'Sad woman';
        $faber[ 'baz' ] = 'I am happy';
        assertInstanceOf( 'GM\Faber', $freeze );
        assertEquals( 'bar', $faber[ 'foo' ] );
        assertEquals( 'baz', $faber[ 'bar' ] );
        assertEquals( 'I am happy', $faber[ 'baz' ] );
    }

    function testFreezeError() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'foo' ] = 'bar';
        $freeze = $faber->freeze( 'bar' );
        assertInstanceOf( 'WP_Error', $freeze );
    }

    function testFreezeFactories() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub' ] = function() {
            $stub = new Stub;
            $stub->id = 'stub';
            return $stub;
        };
        $faber[ 'stub2' ] = function() {
            $stub = new Stub;
            $stub->id = 'stub2';
            return $stub;
        };
        $freeze = $faber->freeze( 'stub' );
        $faber[ 'stub' ] = function() {
            $stub = new Stub;
            $stub->id = 'updated stub';
            return $stub;
        };
        $faber[ 'stub2' ] = function() {
            $stub = new Stub;
            $stub->id = 'updated stub2';
            return $stub;
        };
        assertInstanceOf( 'GM\Faber', $freeze );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $faber[ 'stub' ] );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $faber[ 'stub2' ] );
        assertEquals( 'stub', $faber[ 'stub' ]->id );
        assertEquals( 'updated stub2', $faber[ 'stub2' ]->id );
    }

    function testFreezeObjects() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub' ] = function( $f, $args = [ ] ) {
            $stub = new Stub;
            $stub->id = 'stub';
            $stub->args = $args;
            return $stub;
        };
        $faber[ 'stub2' ] = function( $f, $args = [ ] ) {
            $stub = new Stub;
            $stub->id = 'stub2';
            $stub->args = $args;
            return $stub;
        };
        $a = $faber->get( 'stub', [ 'a' ] );
        $faber->freeze( 'stub' );
        $b = $faber->get( 'stub', [ 'b' ] );
        $c = $faber->get( 'stub2', [ 'c' ] );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $a );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $b );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $c );
        $akey = $faber->getObjectKey( 'stub', [ 'a' ] );
        $bkey = $faber->getObjectKey( 'stub', [ 'b' ] );
        $ckey = $faber->getObjectKey( 'stub2', [ 'c' ] );
        assertTrue( $faber->isFrozen( $akey ) );
        assertTrue( $faber->isFrozen( $bkey ) );
        assertFalse( $faber->isFrozen( $ckey ) );
        unset( $faber[ 'stub' ] );
        unset( $faber[ 'stub2' ] );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $faber->get( 'stub' ) );
        assertInstanceOf( 'WP_Error', $faber->get( 'stub2' ) );
    }

    function testUnfreezeErrorWrongId() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'foo' ] = 'bar';
        $unfreeze = $faber->unfreeze( 'bar' );
        assertInstanceOf( 'WP_Error', $unfreeze );
    }

    function testUnfreezeErrorNotFreezed() {
        $faber = \Mockery::mock( 'GM\Faber' )->makePartial();
        $faber->shouldReceive( 'isFrozen' )->with( 'foo' )->once()->andReturn( FALSE );
        $faber[ 'foo' ] = 'bar';
        $unfreeze = $faber->unfreeze( 'foo' );
        assertInstanceOf( 'WP_Error', $unfreeze );
    }

    function testUnfreezeAfterFreeze() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'foo' ] = 'bar';
        $faber[ 'bar' ] = 'baz';
        $faber[ 'baz' ] = 'foo';
        $faber->freeze( 'foo' );
        $faber->freeze( 'bar' );
        $faber[ 'foo' ] = 'Sad man';
        $faber[ 'bar' ] = 'Sad woman';
        $faber[ 'baz' ] = 'I am happy';
        assertEquals( 'bar', $faber[ 'foo' ] );
        assertEquals( 'baz', $faber[ 'bar' ] );
        assertEquals( 'I am happy', $faber[ 'baz' ] );
        $faber->unfreeze( 'foo' );
        $faber->unfreeze( 'bar' );
        $faber[ 'foo' ] = 'Happy man';
        $faber[ 'bar' ] = 'Happy woman';
        assertEquals( 'Happy man', $faber[ 'foo' ] );
        assertEquals( 'Happy woman', $faber[ 'bar' ] );
    }

    function testUnfreezeAfterFreezeObject() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub' ] = function( $f, $args = [ ] ) {
            $stub = new Stub;
            $stub->id = 'stub';
            $stub->args = $args;
            return $stub;
        };
        $faber->freeze( 'stub' );
        $stub = $faber->get( 'stub' );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $stub );
        $key = $faber->getObjectKey( 'stub' );
        assertTrue( $faber->isFrozen( $key ) );
        $faber->unfreeze( $key );
        assertFalse( $faber->isFrozen( $key ) );
        assertTrue( $faber->isFrozen( 'stub' ) );
    }

    function testUnfreezeAfterFreezeObjects() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub' ] = function( $f, $args = [ ] ) {
            $stub = new Stub;
            $stub->id = 'stub';
            $stub->args = $args;
            return $stub;
        };
        $a = $faber->get( 'stub', [ 'a' ] );
        $faber->freeze( 'stub' );
        $b = $faber->get( 'stub', [ 'b' ] );
        $akey = $faber->getObjectKey( 'stub', [ 'a' ] );
        $bkey = $faber->getObjectKey( 'stub', [ 'b' ] );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $a );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $b );
        assertTrue( $faber->isFrozen( 'stub' ) );
        assertTrue( $faber->isFrozen( $akey ) );
        assertTrue( $faber->isFrozen( $bkey ) );
        $faber->unfreeze( 'stub' );
        assertFalse( $faber->isFrozen( 'stub' ) );
        assertFalse( $faber->isFrozen( $akey ) );
        assertFalse( $faber->isFrozen( $bkey ) );
    }

    function testUpdateErrorWrongId() {
        $faber = $this->getFaber( 'foo' );
        $upd = $faber->update( 'foo', 'bar' );
        assertInstanceOf( 'WP_Error', $upd );
    }

    function testUpdateErrorFrozen() {
        $faber = \Mockery::mock( 'GM\Faber' )->makePartial();
        $faber->shouldReceive( 'isFrozen' )->with( 'foo' )->once()->andReturn( TRUE );
        $faber[ 'foo' ] = 'bar';
        $upd = $faber->update( 'foo', 'hello' );
        assertInstanceOf( 'WP_Error', $upd );
    }

    function testUpdateErrorClosureNotClosure() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub' ] = function() {
            return new Stub;
        };
        $upd = $faber->update( 'stub', 'bar' );
        assertInstanceOf( 'WP_Error', $upd );
    }

    function testUpdate() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'foo' ] = 'bar';
        $faber[ 'bar' ] = 'baz';
        $faber[ 'stub' ] = function() {
            $stub = new Stub;
            $stub->id = 'old';
            return $stub;
        };
        $old_stub_key = $faber->getObjectKey( 'stub' );
        assertEquals( 'bar', $faber[ 'foo' ] );
        assertEquals( 'baz', $faber[ 'bar' ] );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $faber[ 'stub' ] );
        assertEquals( 'old', $faber[ 'stub' ]->id );
        assertTrue( $faber->isCachedObject( $old_stub_key ) );
        $faber->update( 'foo', 'new foo' );
        $faber->update( 'bar', 'new bar' );
        $stub = function() {
            $stub = new Stub;
            $stub->id = 'new';
            return $stub;
        };
        $faber->update( 'stub', $stub );
        assertEquals( 'new foo', $faber[ 'foo' ] );
        assertEquals( 'new bar', $faber[ 'bar' ] );
        $new_stub = $faber->get( 'stub', [ 'foo' ] );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $new_stub );
        assertEquals( 'new', $new_stub->id );
        assertFalse( $faber->isCachedObject( $old_stub_key ) );
    }

    function testRemoveErrorWrongId() {
        $faber = \Mockery::mock( 'GM\Faber' )->makePartial();
        $faber->shouldReceive( 'offsetExists' )->with( 'foo' )->atLeast( 1 )->andReturn( FALSE );
        $remove = $faber->remove( 'foo' );
        assertInstanceOf( 'WP_Error', $remove );
    }

    function testRemoveErrorFrozen() {
        $faber = \Mockery::mock( 'GM\Faber' )->makePartial();
        $faber->shouldReceive( 'isFrozen' )->with( 'foo' )->atLeast( 1 )->andReturn( TRUE );
        $remove = $faber->remove( 'foo' );
        assertInstanceOf( 'WP_Error', $remove );
    }

    function testRemove() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'foo' ] = 'bar';
        $faber[ 'bar' ] = 'baz';
        $faber[ 'stub' ] = function() {
            $stub = new Stub;
            $stub->id = 'stub';
            return $stub;
        };
        $faber[ 'stub2' ] = function() {
            $stub = new Stub;
            $stub->id = 'stub2';
            return $stub;
        };
        $key = $faber->getObjectKey( 'stub' );
        $key2 = $faber->getObjectKey( 'stub2' );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $faber[ 'stub' ] );
        assertInstanceOf( 'GM\Faber\Tests\Stub', $faber[ 'stub2' ] );
        assertTrue( $faber->isCachedObject( $key ) );
        assertTrue( $faber->isCachedObject( $key2 ) );
        assertTrue( $faber->offsetExists( 'foo' ) );
        assertTrue( $faber->offsetExists( 'bar' ) );
        assertTrue( $faber->isCachedObject( $key ) );
        assertTrue( $faber->isCachedObject( $key2 ) );
        $faber->remove( 'foo' );
        $faber->remove( 'bar' );
        $faber->remove( $key );
        assertFalse( $faber->offsetExists( 'foo' ) );
        assertFalse( $faber->offsetExists( 'bar' ) );
        assertFalse( $faber->isCachedObject( $key ) );
        assertTrue( $faber->isCachedObject( $key2 ) );
        assertTrue( $faber->offsetExists( 'stub' ) );
        $faber->remove( 'stub' );
        $faber->remove( 'stub2' );
        assertFalse( $faber->offsetExists( 'stub' ) );
        assertFalse( $faber->offsetExists( 'stub2' ) );
        assertFalse( $faber->isCachedObject( $key2 ) );
    }

    function testRemoveCachedObjectsWhenRemovedFactory() {
        $faber = $this->getFaber( 'foo' );
        $faber[ 'stub' ] = function( $faber, $args ) {
            $stub = new Stub;
            $stub->args = $args;
            return $stub;
        };
        $faber->get( 'stub', [ 'foo' ] );
        $faber->get( 'stub', [ 'bar' ] );
        $info = $faber->getObjectsInfo();
        assertTrue( is_array( $info ) );
        assertTrue( count( $info ) === 2 );
        foreach ( $info as $key => $oi ) {
            assertTrue( is_array( $oi ) );
            assertTrue( isset( $oi[ 'class' ] ) && $oi[ 'class' ] === 'GM\Faber\Tests\Stub' );
        }
        $faber->remove( 'stub' );
        $info_after = $faber->getObjectsInfo();
        assertTrue( is_array( $info_after ) );
        assertTrue( empty( $info_after ) );
    }

    function testGetInfo() {
        $faber = \Mockery::mock( 'GM\Faber' )->makePartial();
        $id = 'test';
        $hash = spl_object_hash( $faber );
        $factories = [ 'stub', 'stub2' ];
        $frozen = [ 'foo' ];
        $closure = function() {
            return 'Hello';
        };
        $properties = [
            'foo'     => 'bar',
            'bar'     => 'baz',
            'closure' => $closure
        ];
        $properties_info = [
            'foo'     => 'bar',
            'bar'     => 'baz',
            'closure' => '{{Anonymous function}}'
        ];
        $objects = [
            "stub_{$hash}"  => [
                'key'      => 'stub_' . $hash,
                'class'    => 'GM\Faber\Tests\Stub',
                'num_args' => '0'
            ],
            "stub2_{$hash}" => [
                'key'      => 'stub2_' . $hash,
                'class'    => 'GM\Faber\Tests\Stub',
                'num_args' => '0'
            ],
        ];
        $objects_info = [
            'stub'  => [ (object) $objects[ "stub_{$hash}" ] ],
            'stub2' => [ (object) $objects[ "stub2_{$hash}" ] ]
        ];
        $faber->shouldReceive( 'getId' )->andReturn( $id );
        $faber->shouldReceive( 'getHash' )->andReturn( $hash );
        $faber->shouldReceive( 'getFactoryIds' )->andReturn( $factories );
        $faber->shouldReceive( 'getFrozenIds' )->andReturn( $frozen );
        $faber->shouldReceive( 'isProtected' )->with( 'closure' )->andReturn( TRUE );
        $faber->shouldReceive( 'getPropIds' )->andReturn( array_keys( $properties ) );
        foreach ( $properties as $key => $val ) {
            $faber->shouldReceive( 'prop' )->with( $key )->andReturn( $val );
        }
        $faber->shouldReceive( 'getObjectsInfo' )->andReturn( $objects );
        $faber->shouldReceive( 'getObjectKey' )->andReturnUsing( function( $id ) use($hash) {
            return "{$id}_{$hash}";
        } );
        $faber->shouldReceive( 'getObjectIndex' )->andReturnUsing( function( $val ) use($hash) {
            return str_replace( "_{$hash}", '', $val );
        } );
        $expected = (object) [
                'id'             => $id,
                'hash'           => $hash,
                'frozen'         => $frozen,
                'factories'      => $factories,
                'properties'     => $properties_info,
                'cached_objects' => $objects_info
        ];
        assertEquals( $expected, $faber->getInfo() );
    }

    function testGetFactoryIds() {
        $faber = \Mockery::mock( 'GM\Faber' )->makePartial();
        $faber->shouldReceive( 'isProtected' )->with( 'stub' )->andReturn( FALSE );
        $faber->shouldReceive( 'isProtected' )->with( 'closure' )->andReturn( TRUE );
        $faber->shouldReceive( 'getFrozenIds' )->andReturn( [ 'foo' ] );
        $faber[ 'foo' ] = 'bar';
        $faber[ 'bar' ] = 'baz';
        $faber[ 'stub' ] = function() {
            $stub = new Stub;
            $stub->id = 'stub';
            return $stub;
        };
        $faber[ 'closure' ] = function() {
            return 'Hello';
        };
        assertEquals( [ 'stub' ], $faber->getFactoryIds() );
    }

    function testGetPropIds() {
        $faber = \Mockery::mock( 'GM\Faber' )->makePartial();
        $faber->shouldReceive( 'isProtected' )->with( 'stub' )->andReturn( FALSE );
        $faber->shouldReceive( 'isProtected' )->with( 'closure' )->andReturn( TRUE );
        $faber[ 'foo' ] = 'bar';
        $faber[ 'bar' ] = 'baz';
        $faber[ 'stub' ] = function() {
            $stub = new Stub;
            $stub->id = 'stub';
            return $stub;
        };
        $faber[ 'closure' ] = function() {
            return 'Hello';
        };
        assertEquals( [ 'foo', 'bar', 'closure' ], $faber->getPropIds() );
    }

    function testGetObjectsInfo() {
        $faber = \Mockery::mock( 'GM\Faber' )->makePartial();
        $faber->shouldReceive( 'getObjectKey' )->with( 'stub', [ ] )->andReturn( 'stub_key' );
        $faber[ 'stub' ] = function() {
            $stub = new Stub;
            $stub->id = 'stub';
            return $stub;
        };
        $stub = $faber[ 'stub' ];
        $expected = [ 'stub_key' => [
                'key'      => 'stub_key',
                'class'    => 'GM\Faber\Tests\Stub',
                'num_args' => 0
            ] ];
        assertInstanceOf( 'GM\Faber\Tests\Stub', $stub );
        assertEquals( $expected, $faber->getObjectsInfo() );
    }

    function testJsonEncoding() {
        $expected = (object) [ 'foo' => 'bar' ];
        $faber = \Mockery::mock( 'GM\Faber' )->makePartial();
        $faber->shouldReceive( 'getInfo' )->withNoArgs()->andReturn( $expected );
        $faber[ 'foo' ] = 'bar';
        $faber[ 'bar' ] = 'baz';
        $faber[ 'stub' ] = function() {
            $stub = new Stub;
            $stub->id = 'old';
            return $stub;
        };
        assertEquals( json_encode( $faber ), json_encode( $expected ) );
    }

    function testError() {
        $faber = $this->getFaber( 'foo' );
        $err = $faber->error( 'foo', 'Foo! %s %s', [ 'Bar!', 'Baz!' ] );
        $expected = [ 'code' => 'faber-foo', 'message' => 'Foo! Bar! Baz!', 'data' => '' ];
        assertInstanceOf( 'WP_Error', $err );
        assertContains( $expected, $err->errors[ 'faber-foo' ] );
    }

}