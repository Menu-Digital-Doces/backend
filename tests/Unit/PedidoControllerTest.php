<?php

namespace Tests\Unit;

use App\Http\Controllers\PedidoController;
use App\Models\Estoque;
use App\Models\Pedido;
use App\Models\Produto;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class PedidoControllerTest extends TestCase
{
    protected PedidoController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new PedidoController();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function index_retorna_pedidos_do_usuario_com_sucesso()
    {
        // Arrange: Mock do QueryBuilder
        $queryBuilder = Mockery::mock(QueryBuilder::class);
        
        $queryBuilder->shouldReceive('join')
            ->once()
            ->with('users', 'pedidos.user_id', '=', 'users.id')
            ->andReturnSelf();
        
        $queryBuilder->shouldReceive('join')
            ->once()
            ->with('produtos', 'pedidos.produto_id', '=', 'produtos.id')
            ->andReturnSelf();
        
        $queryBuilder->shouldReceive('select')
            ->once()
            ->with('pedidos.*', 'users.name as user_name', 'users.email as user_email', 'users.id as user_id')
            ->andReturnSelf();
        
        $queryBuilder->shouldReceive('where')
            ->once()
            ->with('users.id', 3)
            ->andReturnSelf();
        
        $pedidosEsperados = collect([
            (object)[
                'id' => 1,
                'codigo' => 'PED-20251014-ABC123',
                'user_id' => 3,
                'produto_id' => 1,
                'quantidade' => 5,
                'total' => 500.00,
                'status' => 'Pendente',
                'user_name' => 'João Silva',
                'user_email' => 'joao@example.com',
            ]
        ]);
        
        $queryBuilder->shouldReceive('get')
            ->once()
            ->andReturn($pedidosEsperados);
        
        DB::shouldReceive('table')
            ->once()
            ->with('pedidos')
            ->andReturn($queryBuilder);

        $request = Mockery::mock(Request::class);

        // Act
        $response = $this->controller->index($request);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($pedidosEsperados, $response->getData());
    }

    /** @test */
    public function show_retorna_pedido_especifico_com_sucesso()
    {
        // Arrange: Mock do QueryBuilder
        $queryBuilder = Mockery::mock(QueryBuilder::class);
        
        $queryBuilder->shouldReceive('join')
            ->once()
            ->with('users', 'pedidos.user_id', '=', 'users.id')
            ->andReturnSelf();
        
        $queryBuilder->shouldReceive('join')
            ->once()
            ->with('produtos', 'pedidos.produto_id', '=', 'produtos.id')
            ->andReturnSelf();
        
        $queryBuilder->shouldReceive('select')
            ->once()
            ->with('pedidos.*', 'users.name as user_name', 'users.email as user_email', 'users.id as user_id')
            ->andReturnSelf();
        
        $queryBuilder->shouldReceive('where')
            ->once()
            ->with('pedidos.id', 1)
            ->andReturnSelf();
        
        $pedidoEsperado = (object)[
            'id' => 1,
            'codigo' => 'PED-20251014-ABC123',
            'user_id' => 3,
            'produto_id' => 1,
            'quantidade' => 5,
            'total' => 500.00,
            'status' => 'Pendente',
            'user_name' => 'João Silva',
            'user_email' => 'joao@example.com',
        ];
        
        $queryBuilder->shouldReceive('first')
            ->once()
            ->andReturn($pedidoEsperado);
        
        DB::shouldReceive('table')
            ->once()
            ->with('pedidos')
            ->andReturn($queryBuilder);

        // Act
        $response = $this->controller->show(1);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($pedidoEsperado, $response->getData());
    }

    /** @test */
    public function update_atualiza_status_do_pedido_com_sucesso()
    {
        // Arrange: Mock do modelo Pedido
        $pedidoMock = Mockery::mock(Pedido::class);
        $pedidoMock->shouldReceive('update')
            ->once()
            ->with(['status' => 'Confirmado'])
            ->andReturn(true);
        
        $pedidoMock->id = 1;
        $pedidoMock->codigo = 'PED-20251014-ABC123';
        $pedidoMock->status = 'Confirmado';
        $pedidoMock->total = 500.00;
        $pedidoMock->quantidade = 5;

        Pedido::shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($pedidoMock);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->with(['status' => 'required|in:Pendente,Confirmado,Cancelado'])
            ->andReturn(['status' => 'Confirmado']);

        // Act
        $response = $this->controller->update($request, 1);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getData();
        $this->assertEquals('Confirmado', $responseData->status);
    }

    /** @test */
    public function update_retorna_404_quando_pedido_nao_existe()
    {
        // Arrange
        Pedido::shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        $request = Mockery::mock(Request::class);

        // Act
        $response = $this->controller->update($request, 999);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('Pedido não encontrado', $responseData['message']);
    }

    /** @test */
    public function destroy_deleta_pedido_com_sucesso()
    {
        // Arrange: Mock do modelo Pedido
        $pedidoMock = Mockery::mock(Pedido::class);
        $pedidoMock->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        Pedido::shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($pedidoMock);

        // Act
        $response = $this->controller->destroy(1);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('Pedido deletado com sucesso', $responseData['message']);
    }

    /** @test */
    public function destroy_retorna_404_quando_pedido_nao_existe()
    {
        // Arrange
        Pedido::shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturn(null);

        // Act
        $response = $this->controller->destroy(999);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('Pedido não encontrado', $responseData['message']);
    }

    /** @test */
    public function store_cria_pedido_com_sucesso()
    {
        // Arrange: Mock dos modelos
        $produtoMock = Mockery::mock(Produto::class);
        $produtoMock->id = 1;
        $produtoMock->nome = 'Bolo de Chocolate';
        $produtoMock->preco = 100.00;
        $produtoMock->quantidade = 50;
        $produtoMock->status = 'Ativo';
        
        $produtoMock->shouldReceive('getAttribute')
            ->with('status')
            ->andReturn('Ativo');
        
        $produtoMock->shouldReceive('getAttribute')
            ->with('quantidade')
            ->andReturn(50);
        
        $produtoMock->shouldReceive('getAttribute')
            ->with('preco')
            ->andReturn(100.00);
        
        $produtoMock->shouldReceive('getAttribute')
            ->with('nome')
            ->andReturn('Bolo de Chocolate');
        
        $produtoMock->shouldReceive('decrement')
            ->once()
            ->with('quantidade', 5)
            ->andReturn(true);

        Produto::shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($produtoMock);

        $estoqueMock = Mockery::mock(Estoque::class);
        $estoqueMock->quantidade = 50;
        $estoqueMock->shouldReceive('getAttribute')
            ->with('quantidade')
            ->andReturn(50);
        $estoqueMock->shouldReceive('update')
            ->once()
            ->andReturn(true);

        Estoque::shouldReceive('where')
            ->once()
            ->with('produto_id', 1)
            ->andReturnSelf();
        Estoque::shouldReceive('first')
            ->once()
            ->andReturn($estoqueMock);

        $pedidoMock = Mockery::mock(Pedido::class);
        $pedidoMock->id = 1;
        $pedidoMock->codigo = 'PED-20251014-ABC123';
        $pedidoMock->total = 500.00;
        $pedidoMock->quantidade = 5;
        $pedidoMock->shouldReceive('getAttribute')
            ->with('total')
            ->andReturn(500.00);

        Pedido::shouldReceive('create')
            ->once()
            ->andReturn($pedidoMock);

        Pedido::shouldReceive('where')
            ->with('codigo', Mockery::any())
            ->andReturnSelf();
        Pedido::shouldReceive('exists')
            ->andReturn(false);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn([
                'itens' => [
                    ['produto_id' => 1, 'quantidade' => 5]
                ]
            ]);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('Pedido criado com sucesso', $responseData['message']);
        $this->assertArrayHasKey('codigo', $responseData);
        $this->assertEquals(500.00, $responseData['total_geral']);
    }

    /** @test */
    public function store_retorna_erro_quando_estoque_nao_encontrado()
    {
        // Arrange
        Estoque::shouldReceive('where')
            ->once()
            ->with('produto_id', 1)
            ->andReturnSelf();
        Estoque::shouldReceive('first')
            ->once()
            ->andReturn(null);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn([
                'itens' => [
                    ['produto_id' => 1, 'quantidade' => 5]
                ]
            ]);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('Erro ao criar pedido', $responseData['message']);
        $this->assertEquals('Estoque não encontrado para o produto.', $responseData['error']);
    }

    /** @test */
    public function store_retorna_erro_quando_estoque_insuficiente()
    {
        // Arrange
        $estoqueMock = Mockery::mock(Estoque::class);
        $estoqueMock->quantidade = 3;
        $estoqueMock->shouldReceive('getAttribute')
            ->with('quantidade')
            ->andReturn(3);

        Estoque::shouldReceive('where')
            ->once()
            ->with('produto_id', 1)
            ->andReturnSelf();
        Estoque::shouldReceive('first')
            ->once()
            ->andReturn($estoqueMock);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn([
                'itens' => [
                    ['produto_id' => 1, 'quantidade' => 10]
                ]
            ]);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('Erro ao criar pedido', $responseData['message']);
        $this->assertEquals('Estoque insuficiente para o produto.', $responseData['error']);
    }

    /** @test */
    public function store_retorna_erro_quando_produto_inativo()
    {
        // Arrange
        $estoqueMock = Mockery::mock(Estoque::class);
        $estoqueMock->quantidade = 50;
        $estoqueMock->shouldReceive('getAttribute')
            ->with('quantidade')
            ->andReturn(50);

        Estoque::shouldReceive('where')
            ->once()
            ->with('produto_id', 1)
            ->andReturnSelf();
        Estoque::shouldReceive('first')
            ->once()
            ->andReturn($estoqueMock);

        $produtoMock = Mockery::mock(Produto::class);
        $produtoMock->nome = 'Bolo de Chocolate';
        $produtoMock->status = 'Inativo';
        $produtoMock->shouldReceive('getAttribute')
            ->with('status')
            ->andReturn('Inativo');
        $produtoMock->shouldReceive('getAttribute')
            ->with('nome')
            ->andReturn('Bolo de Chocolate');

        Produto::shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($produtoMock);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn([
                'itens' => [
                    ['produto_id' => 1, 'quantidade' => 5]
                ]
            ]);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('Erro ao criar pedido', $responseData['message']);
        $this->assertEquals("Produto 'Bolo de Chocolate' não está disponível.", $responseData['error']);
    }

    /** @test */
    public function store_retorna_erro_quando_quantidade_produto_insuficiente()
    {
        // Arrange
        $estoqueMock = Mockery::mock(Estoque::class);
        $estoqueMock->quantidade = 50;
        $estoqueMock->shouldReceive('getAttribute')
            ->with('quantidade')
            ->andReturn(50);

        Estoque::shouldReceive('where')
            ->once()
            ->with('produto_id', 1)
            ->andReturnSelf();
        Estoque::shouldReceive('first')
            ->once()
            ->andReturn($estoqueMock);

        $produtoMock = Mockery::mock(Produto::class);
        $produtoMock->nome = 'Bolo de Chocolate';
        $produtoMock->status = 'Ativo';
        $produtoMock->quantidade = 3;
        $produtoMock->shouldReceive('getAttribute')
            ->with('status')
            ->andReturn('Ativo');
        $produtoMock->shouldReceive('getAttribute')
            ->with('quantidade')
            ->andReturn(3);
        $produtoMock->shouldReceive('getAttribute')
            ->with('nome')
            ->andReturn('Bolo de Chocolate');

        Produto::shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($produtoMock);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn([
                'itens' => [
                    ['produto_id' => 1, 'quantidade' => 10]
                ]
            ]);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('Erro ao criar pedido', $responseData['message']);
        $this->assertEquals("Estoque insuficiente para 'Bolo de Chocolate'.", $responseData['error']);
    }

    /** @test */
    public function gerar_codigo_unico_retorna_formato_correto()
    {
        // Arrange
        Pedido::shouldReceive('where')
            ->with('codigo', Mockery::any())
            ->andReturnSelf();
        Pedido::shouldReceive('exists')
            ->andReturn(false);

        // Act: Usar reflexão para acessar método privado
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('gerarCodigoUnico');
        $method->setAccessible(true);
        
        $codigo = $method->invoke($this->controller);

        // Assert
        $this->assertIsString($codigo);
        $this->assertStringStartsWith('PED-', $codigo);
        $this->assertMatchesRegularExpression('/^PED-\d{8}-[A-Z0-9]{6}$/', $codigo);
    }

    /** @test */
    public function gerar_codigo_unico_gera_codigos_diferentes()
    {
        // Arrange
        Pedido::shouldReceive('where')
            ->with('codigo', Mockery::any())
            ->andReturnSelf();
        Pedido::shouldReceive('exists')
            ->andReturn(false);

        // Act: Gerar múltiplos códigos
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('gerarCodigoUnico');
        $method->setAccessible(true);
        
        $codigos = [];
        for ($i = 0; $i < 10; $i++) {
            $codigos[] = $method->invoke($this->controller);
        }

        // Assert: Verificar que todos são únicos
        $codigosUnicos = array_unique($codigos);
        $this->assertCount(10, $codigosUnicos);
    }

    /** @test */
    public function gerar_codigo_unico_contem_data_atual()
    {
        // Arrange
        Pedido::shouldReceive('where')
            ->with('codigo', Mockery::any())
            ->andReturnSelf();
        Pedido::shouldReceive('exists')
            ->andReturn(false);

        // Act
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('gerarCodigoUnico');
        $method->setAccessible(true);
        
        $codigo = $method->invoke($this->controller);
        $dataAtual = date('Ymd');

        // Assert
        $this->assertStringContainsString($dataAtual, $codigo);
    }

    /** @test */
    public function gerar_codigo_unico_tem_comprimento_correto()
    {
        // Arrange
        Pedido::shouldReceive('where')
            ->with('codigo', Mockery::any())
            ->andReturnSelf();
        Pedido::shouldReceive('exists')
            ->andReturn(false);

        // Act
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('gerarCodigoUnico');
        $method->setAccessible(true);
        
        $codigo = $method->invoke($this->controller);

        // Assert: PED- (4) + data (8) + - (1) + random (6) = 19
        $this->assertEquals(19, strlen($codigo));
    }

    /** @test */
    public function gerar_codigo_unico_usa_apenas_letras_maiusculas_e_numeros()
    {
        // Arrange
        Pedido::shouldReceive('where')
            ->with('codigo', Mockery::any())
            ->andReturnSelf();
        Pedido::shouldReceive('exists')
            ->andReturn(false);

        // Act
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('gerarCodigoUnico');
        $method->setAccessible(true);
        
        for ($i = 0; $i < 20; $i++) {
            $codigo = $method->invoke($this->controller);
            
            // Assert: Verificar que não contém letras minúsculas
            $this->assertDoesNotMatchRegularExpression('/[a-z]/', $codigo);
            
            // Extrair a parte aleatória (últimos 6 caracteres)
            $parteAleatoria = substr($codigo, -6);
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $parteAleatoria);
        }
    }

    /** @test */
    public function gerar_codigo_unico_tenta_novamente_quando_codigo_existe()
    {
        // Arrange: Simular que o primeiro código já existe
        Pedido::shouldReceive('where')
            ->with('codigo', Mockery::any())
            ->andReturnSelf();
        
        Pedido::shouldReceive('exists')
            ->once()
            ->andReturn(true); // Primeira tentativa: código existe
        
        Pedido::shouldReceive('exists')
            ->once()
            ->andReturn(false); // Segunda tentativa: código único

        // Act
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('gerarCodigoUnico');
        $method->setAccessible(true);
        
        $codigo = $method->invoke($this->controller);

        // Assert: Deve ter gerado um código válido após a segunda tentativa
        $this->assertIsString($codigo);
        $this->assertStringStartsWith('PED-', $codigo);
    }

    /** @test */
    public function gerar_codigo_unico_lanca_excecao_apos_max_tentativas()
    {
        // Arrange: Simular que o código sempre existe (max 10 tentativas)
        Pedido::shouldReceive('where')
            ->with('codigo', Mockery::any())
            ->andReturnSelf();
        
        Pedido::shouldReceive('exists')
            ->andReturn(true); // Sempre retorna true

        // Assert: Esperar exceção
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Não foi possível gerar um código único.');

        // Act
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('gerarCodigoUnico');
        $method->setAccessible(true);
        
        $method->invoke($this->controller);
    }
}