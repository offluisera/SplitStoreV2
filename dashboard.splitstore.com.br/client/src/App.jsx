import React, { useState, useEffect } from 'react';
import {
  LayoutDashboard,
  Users,
  Package,
  Palette,
  Newspaper,
  Server,
  CreditCard,
  Settings,
  LogOut,
  Menu,
  X,
  TrendingUp,
  ShoppingCart,
  DollarSign,
  Eye,
  Plus,
  Search,
  Filter,
  MoreVertical,
  Edit,
  Trash2,
  ChevronRight,
  Zap,
  Shield,
  BarChart3,
  Clock,
  CheckCircle,
  XCircle,
  AlertCircle,
  Download,
  Upload,
  Image,
  Type,
  Layout,
  Sparkles,
  Globe,
  Link2
} from 'lucide-react';

// ============================================
// COMPONENTE: Sidebar
// ============================================
const Sidebar = ({ activeMenu, setActiveMenu, userPlan, sidebarOpen, setSidebarOpen }) => {
  const menuItems = userPlan ? [
    { id: 'dashboard', icon: <LayoutDashboard />, label: 'Dashboard' },
    { id: 'users', icon: <Users />, label: 'Usuários' },
    { id: 'products', icon: <Package />, label: 'Produtos' },
    { id: 'customization', icon: <Palette />, label: 'Customização' },
    { id: 'news', icon: <Newspaper />, label: 'Notícias' },
    { id: 'servers', icon: <Server />, label: 'Servidores' },
    { id: 'settings', icon: <Settings />, label: 'Configurações' }
  ] : [
    { id: 'plans', icon: <CreditCard />, label: 'Planos' }
  ];

  return (
    <>
      {/* Overlay mobile */}
      {sidebarOpen && (
        <div 
          className="fixed inset-0 bg-black/50 z-40 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside className={`fixed left-0 top-0 h-full w-72 bg-gradient-to-b from-zinc-950 to-black border-r border-white/5 z-50 transform transition-transform duration-300 ${
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
      } lg:translate-x-0`}>
        <div className="flex flex-col h-full">
          {/* Logo */}
          <div className="p-6 border-b border-white/5">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-gradient-to-br from-red-600 to-red-900 rounded-xl flex items-center justify-center font-black text-lg">
                  S
                </div>
                <div>
                  <h1 className="text-xl font-black">Split<span className="text-red-600">Store</span></h1>
                  {userPlan && <p className="text-xs text-zinc-500">Plano {userPlan}</p>}
                </div>
              </div>
              <button onClick={() => setSidebarOpen(false)} className="lg:hidden">
                <X className="w-6 h-6" />
              </button>
            </div>
          </div>

          {/* Menu */}
          <nav className="flex-1 p-4 overflow-y-auto">
            <div className="space-y-1">
              {menuItems.map(item => (
                <button
                  key={item.id}
                  onClick={() => {
                    setActiveMenu(item.id);
                    setSidebarOpen(false);
                  }}
                  className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all ${
                    activeMenu === item.id
                      ? 'bg-red-600 text-white shadow-lg shadow-red-600/50'
                      : 'text-zinc-400 hover:bg-white/5 hover:text-white'
                  }`}
                >
                  <div className="w-5 h-5">{item.icon}</div>
                  <span className="font-semibold">{item.label}</span>
                </button>
              ))}
            </div>
          </nav>

          {/* Footer */}
          <div className="p-4 border-t border-white/5">
            <button className="w-full flex items-center gap-3 px-4 py-3 text-zinc-400 hover:bg-white/5 hover:text-white rounded-xl transition-all">
              <LogOut className="w-5 h-5" />
              <span className="font-semibold">Sair</span>
            </button>
          </div>
        </div>
      </aside>
    </>
  );
};

// ============================================
// COMPONENTE: Header
// ============================================
const Header = ({ title, setSidebarOpen }) => {
  return (
    <header className="bg-gradient-to-r from-zinc-950 to-black border-b border-white/5 px-6 py-4 sticky top-0 z-30">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <button onClick={() => setSidebarOpen(true)} className="lg:hidden">
            <Menu className="w-6 h-6" />
          </button>
          <h2 className="text-2xl font-black">{title}</h2>
        </div>
        <div className="flex items-center gap-4">
          <div className="hidden md:flex items-center gap-2 bg-white/5 rounded-xl px-4 py-2 border border-white/10">
            <Search className="w-4 h-4 text-zinc-500" />
            <input 
              type="text" 
              placeholder="Buscar..."
              className="bg-transparent border-none outline-none text-sm w-64"
            />
          </div>
          <div className="w-10 h-10 bg-gradient-to-br from-red-600 to-red-900 rounded-xl flex items-center justify-center font-bold cursor-pointer">
            L
          </div>
        </div>
      </div>
    </header>
  );
};

// ============================================
// COMPONENTE: DashboardHome
// ============================================
const DashboardHome = () => {
  const stats = [
    {
      label: 'Vendas hoje',
      value: 'R$ 0,00',
      subtitle: 'Hoje',
      icon: <BarChart3 className="w-6 h-6" />,
      color: 'from-purple-600/20 to-purple-900/20',
      borderColor: 'border-purple-600/30',
      iconBg: 'bg-purple-600/20',
      iconColor: 'text-purple-400'
    },
    {
      label: 'Total em vendas',
      value: 'R$ 0,00',
      subtitle: 'Vendas de todo o período',
      icon: <TrendingUp className="w-6 h-6" />,
      color: 'from-green-600/20 to-emerald-600/20',
      borderColor: 'border-green-600/30',
      iconBg: 'bg-green-600/20',
      iconColor: 'text-green-400'
    }
  ];

  const monthStats = {
    month: 'janeiro',
    sales: 0,
    value: 'R$ 0,00',
    percentage: '100%',
    isPositive: true
  };

  const counters = [
    { label: 'Vendas', value: 0, icon: <TrendingUp className="w-5 h-5" /> },
    { label: 'Produtos Registrados', value: 0, icon: <Package className="w-5 h-5" /> },
    { label: 'Carrinhos Abandonados', value: 0, icon: <ShoppingCart className="w-5 h-5" /> },
    { label: 'Usuários Cadastrados', value: 0, icon: <Users className="w-5 h-5" /> }
  ];

  const weekDays = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S', 'D'];
  const visits = [0, 0, 0, 0, 0, 0, 0, 0];

  return (
    <div className="space-y-6">
      {/* Stats principais - Estilo Kyzeno */}
      <div className="grid md:grid-cols-2 gap-6">
        {stats.map((stat, idx) => (
          <div key={idx} className={`bg-gradient-to-br ${stat.color} border ${stat.borderColor} rounded-2xl p-6`}>
            <div className={`w-12 h-12 ${stat.iconBg} rounded-xl flex items-center justify-center ${stat.iconColor} mb-4`}>
              {stat.icon}
            </div>
            <p className="text-sm text-zinc-400 mb-1">{stat.label}</p>
            <p className="text-4xl font-black mb-2">{stat.value}</p>
            <div className="flex items-center gap-2 text-xs text-zinc-500">
              <Clock className="w-3 h-3" />
              <span>{stat.subtitle}</span>
            </div>
          </div>
        ))}
      </div>

      {/* Vendas do Mês e Visitas */}
      <div className="grid md:grid-cols-2 gap-6">
        {/* Vendas do Mês */}
        <div className="bg-gradient-to-br from-zinc-900/50 to-zinc-950/50 border border-white/10 rounded-2xl p-6">
          <p className="text-sm text-zinc-400 mb-1">
            Vendas em {monthStats.month} ({monthStats.sales})
          </p>
          <p className="text-4xl font-black mb-4">{monthStats.value}</p>
          <div className="flex items-center gap-2">
            <TrendingUp className={`w-4 h-4 ${monthStats.isPositive ? 'text-green-500' : 'text-red-500'}`} />
            <span className={`text-sm font-bold ${monthStats.isPositive ? 'text-green-500' : 'text-red-500'}`}>
              {monthStats.isPositive ? '+' : ''}{monthStats.percentage}
            </span>
          </div>
        </div>

        {/* Visitas na Loja */}
        <div className="bg-gradient-to-br from-zinc-900/50 to-zinc-950/50 border border-white/10 rounded-2xl p-6">
          <div className="flex items-center justify-between mb-4">
            <p className="text-sm text-zinc-400">Visitas na loja</p>
            <span className="text-xs text-zinc-500">Esta semana</span>
          </div>
          <p className="text-5xl font-black mb-4">0</p>
          <div className="flex items-center gap-2 mb-4">
            <TrendingUp className="w-4 h-4 text-red-500" />
            <span className="text-sm font-bold text-red-500">-100%</span>
          </div>
          
          {/* Gráfico simples de barras */}
          <div className="flex items-end justify-between gap-2 h-16">
            {visits.map((visit, idx) => (
              <div key={idx} className="flex-1 flex flex-col items-center gap-1">
                <div 
                  className="w-full bg-zinc-700/50 rounded-t"
                  style={{ height: visit > 0 ? `${(visit / Math.max(...visits)) * 100}%` : '4px' }}
                />
                <span className="text-xs text-zinc-600">{weekDays[idx]}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Contadores */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {counters.map((counter, idx) => (
          <div key={idx} className="bg-gradient-to-br from-zinc-900/50 to-zinc-950/50 border border-white/10 rounded-xl p-6 hover:border-red-600/30 transition-all cursor-pointer">
            <div className="flex items-center justify-between mb-3">
              <div className="w-10 h-10 bg-red-600/20 rounded-lg flex items-center justify-center">
                <div className="text-red-600">{counter.icon}</div>
              </div>
              <span className="text-3xl font-black">{counter.value}</span>
            </div>
            <p className="text-sm text-zinc-400">{counter.label}</p>
          </div>
        ))}
      </div>

      {/* Taxa da plataforma */}
      <div className="bg-gradient-to-br from-blue-600/10 to-indigo-600/10 border border-blue-600/20 rounded-2xl p-6">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="font-bold mb-2 flex items-center gap-2">
              <Zap className="w-5 h-5 text-blue-400" />
              Taxa da Plataforma
            </h3>
            <p className="text-sm text-zinc-400">
              A SplitStore cobra uma taxa de <span className="text-blue-400 font-bold">5% + R$ 1,00</span> por venda realizada.
            </p>
            <p className="text-xs text-zinc-500 mt-2">
              Exemplo: Em uma venda de R$ 100,00, você recebe R$ 94,00.
            </p>
          </div>
        </div>
      </div>

      {/* Próximos passos */}
      <div className="bg-gradient-to-br from-zinc-900/50 to-zinc-950/50 border border-white/10 rounded-2xl p-6">
        <h3 className="font-bold mb-4 flex items-center gap-2">
          <Sparkles className="w-5 h-5 text-yellow-400" />
          Próximos Passos
        </h3>
        <div className="space-y-3">
          {[
            { text: 'Adicione seus primeiros produtos VIP', done: false },
            { text: 'Configure seu servidor Minecraft', done: false },
            { text: 'Personalize o visual da sua loja', done: false },
            { text: 'Publique sua primeira notícia', done: false }
          ].map((step, idx) => (
            <div key={idx} className="flex items-center gap-3 p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-all cursor-pointer">
              <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                step.done ? 'border-green-600 bg-green-600' : 'border-zinc-600'
              }`}>
                {step.done && <CheckCircle className="w-3 h-3" />}
              </div>
              <span className="text-sm">{step.text}</span>
              <ChevronRight className="w-4 h-4 ml-auto text-zinc-500" />
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

// ============================================
// COMPONENTE: PlansPage (sem plano ativo)
// ============================================
const PlansPage = () => {
  const [selectedPlan, setSelectedPlan] = useState(null);

  const plans = [
    {
      id: 'starter',
      name: 'STARTER',
      description: 'Perfeito para começar',
      price: '14,99',
      icon: <Zap className="w-6 h-6" />,
      features: ['1 Servidor Minecraft', 'Checkout Responsivo', 'Suporte via Ticket', 'Plugin de Entrega']
    },
    {
      id: 'enterprise',
      name: 'ENTERPRISE',
      description: 'Para redes sérias',
      price: '25,99',
      icon: <Shield className="w-6 h-6" />,
      features: ['5 Servidores', 'Checkout Customizável', 'Suporte Prioritário 24/7', 'Analytics Avançado'],
      popular: true
    },
    {
      id: 'gerencial',
      name: 'GERENCIAL',
      description: 'Soluções enterprise',
      price: '39,99',
      icon: <TrendingUp className="w-6 h-6" />,
      features: ['Servidores Ilimitados', 'Whitelabel Completo', 'Gerente de Contas', 'Integrações Custom']
    }
  ];

  return (
    <div className="max-w-6xl mx-auto">
      <div className="text-center mb-12">
        <h2 className="text-4xl font-black mb-4">
          Escolha seu <span className="text-red-600">Plano</span>
        </h2>
        <p className="text-zinc-400 text-lg">
          Selecione o plano ideal para o seu negócio
        </p>
      </div>

      <div className="grid md:grid-cols-3 gap-8">
        {plans.map(plan => (
          <div
            key={plan.id}
            onClick={() => setSelectedPlan(plan.id)}
            className={`relative rounded-2xl p-8 cursor-pointer transition-all ${
              selectedPlan === plan.id
                ? 'bg-gradient-to-br from-red-600 to-red-900 scale-105 shadow-2xl'
                : 'bg-white/5 hover:bg-white/10 border border-white/10'
            }`}
          >
            {plan.popular && (
              <div className="absolute -top-4 left-1/2 -translate-x-1/2">
                <div className="bg-red-600 text-white text-xs font-black uppercase px-4 py-1 rounded-full">
                  Mais Popular
                </div>
              </div>
            )}

            <div className="text-center mb-6">
              <div className={`inline-flex w-12 h-12 rounded-xl items-center justify-center mb-4 ${
                selectedPlan === plan.id ? 'bg-white/20' : 'bg-red-600/20'
              }`}>
                <div className={selectedPlan === plan.id ? 'text-white' : 'text-red-600'}>
                  {plan.icon}
                </div>
              </div>
              <h3 className="text-xl font-black mb-2">{plan.name}</h3>
              <p className={`text-sm ${selectedPlan === plan.id ? 'text-red-100' : 'text-zinc-400'}`}>
                {plan.description}
              </p>
            </div>

            <div className="text-center mb-6">
              <div className="flex items-baseline justify-center gap-1">
                <span className="text-sm opacity-70">R$</span>
                <span className="text-5xl font-black">{plan.price}</span>
                <span className="text-sm opacity-70">/mês</span>
              </div>
            </div>

            <div className="space-y-3 mb-8">
              {plan.features.map((feature, idx) => (
                <div key={idx} className="flex items-start gap-2">
                  <CheckCircle className={`w-4 h-4 flex-shrink-0 mt-0.5 ${
                    selectedPlan === plan.id ? 'text-white' : 'text-red-600'
                  }`} />
                  <span className="text-sm">{feature}</span>
                </div>
              ))}
            </div>

            <button className={`w-full py-3 rounded-xl font-bold transition-all ${
              selectedPlan === plan.id
                ? 'bg-white text-red-600 hover:bg-red-50'
                : 'bg-red-600 text-white hover:bg-red-700'
            }`}>
              {selectedPlan === plan.id ? 'Selecionado' : 'Selecionar'}
            </button>
          </div>
        ))}
      </div>

      {selectedPlan && (
        <div className="mt-8 text-center">
          <button className="px-12 py-4 bg-red-600 hover:bg-red-700 rounded-xl font-black uppercase tracking-wider transition-all">
            Continuar com {plans.find(p => p.id === selectedPlan)?.name}
            <ChevronRight className="inline w-5 h-5 ml-2" />
          </button>
        </div>
      )}
    </div>
  );
};

// ============================================
// COMPONENTE: ProductsPage
// ============================================
const ProductsPage = () => {
  const [view, setView] = useState('categories');

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex gap-2">
          <button
            onClick={() => setView('categories')}
            className={`px-4 py-2 rounded-lg font-semibold transition-all ${
              view === 'categories'
                ? 'bg-red-600 text-white'
                : 'bg-white/5 text-zinc-400 hover:bg-white/10'
            }`}
          >
            Categorias
          </button>
          <button
            onClick={() => setView('products')}
            className={`px-4 py-2 rounded-lg font-semibold transition-all ${
              view === 'products'
                ? 'bg-red-600 text-white'
                : 'bg-white/5 text-zinc-400 hover:bg-white/10'
            }`}
          >
            Produtos
          </button>
        </div>
        <button className="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg font-semibold flex items-center gap-2">
          <Plus className="w-5 h-5" />
          Adicionar {view === 'categories' ? 'Categoria' : 'Produto'}
        </button>
      </div>

      <div className="bg-white/5 border border-white/10 rounded-xl p-8 text-center">
        <div className="w-20 h-20 bg-red-600/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <Package className="w-10 h-10 text-red-600" />
        </div>
        <h3 className="text-xl font-bold mb-2">Nenhum {view === 'categories' ? 'categoria' : 'produto'} criado</h3>
        <p className="text-zinc-400 mb-6">
          Comece adicionando {view === 'categories' ? 'categorias' : 'produtos'} para sua loja
        </p>
        <button className="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold">
          Criar {view === 'categories' ? 'Primeira Categoria' : 'Primeiro Produto'}
        </button>
      </div>
    </div>
  );
};

// ============================================
// COMPONENTE: CustomizationPage
// ============================================
const CustomizationPage = () => {
  const [activeTab, setActiveTab] = useState('theme');

  const tabs = [
    { id: 'theme', label: 'Tema', icon: <Palette /> },
    { id: 'logo', label: 'Logo', icon: <Image /> },
    { id: 'layout', label: 'Layout', icon: <Layout /> },
    { id: 'texts', label: 'Textos', icon: <Type /> }
  ];

  return (
    <div className="space-y-6">
      <div className="flex gap-2 border-b border-white/10 pb-4">
        {tabs.map(tab => (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id)}
            className={`px-4 py-2 rounded-lg font-semibold flex items-center gap-2 transition-all ${
              activeTab === tab.id
                ? 'bg-red-600 text-white'
                : 'text-zinc-400 hover:bg-white/5'
            }`}
          >
            {tab.icon}
            {tab.label}
          </button>
        ))}
      </div>

      <div className="bg-white/5 border border-white/10 rounded-xl p-8">
        {activeTab === 'theme' && (
          <div className="space-y-6">
            <h3 className="text-xl font-bold mb-4">Escolha um Tema</h3>
            <div className="grid grid-cols-3 gap-4">
              {['Dark', 'Light', 'Neon'].map(theme => (
                <div key={theme} className="aspect-video bg-gradient-to-br from-zinc-800 to-zinc-900 rounded-xl border border-white/10 flex items-center justify-center cursor-pointer hover:border-red-600 transition-all">
                  <span className="font-bold">{theme}</span>
                </div>
              ))}
            </div>
          </div>
        )}

        {activeTab === 'logo' && (
          <div className="text-center space-y-6">
            <div className="w-32 h-32 bg-white/5 border border-white/10 border-dashed rounded-xl mx-auto flex items-center justify-center">
              <Upload className="w-8 h-8 text-zinc-500" />
            </div>
            <button className="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold">
              Fazer Upload do Logo
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

// ============================================
// COMPONENTE PRINCIPAL: App
// ============================================
const App = () => {
  const [activeMenu, setActiveMenu] = useState('dashboard');
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [userPlan, setUserPlan] = useState('Enterprise');

  const getPageTitle = () => {
    const titles = {
      dashboard: 'Dashboard',
      users: 'Usuários',
      products: 'Produtos',
      customization: 'Customização',
      news: 'Notícias',
      servers: 'Servidores',
      settings: 'Configurações',
      plans: 'Escolha seu Plano'
    };
    return titles[activeMenu] || 'Dashboard';
  };

  const renderContent = () => {
    if (!userPlan && activeMenu !== 'plans') {
      setActiveMenu('plans');
    }

    switch (activeMenu) {
      case 'dashboard':
        return <DashboardHome />;
      case 'products':
        return <ProductsPage />;
      case 'customization':
        return <CustomizationPage />;
      case 'plans':
        return <PlansPage />;
      default:
        return (
          <div className="bg-white/5 border border-white/10 rounded-xl p-12 text-center">
            <h3 className="text-2xl font-bold mb-2">Em construção</h3>
            <p className="text-zinc-400">Esta página está sendo desenvolvida</p>
          </div>
        );
    }
  };

  return (
    <div className="min-h-screen bg-black text-white">
      <Sidebar 
        activeMenu={activeMenu}
        setActiveMenu={setActiveMenu}
        userPlan={userPlan}
        sidebarOpen={sidebarOpen}
        setSidebarOpen={setSidebarOpen}
      />

      <div className="lg:ml-72">
        <Header 
          title={getPageTitle()}
          setSidebarOpen={setSidebarOpen}
        />

        <main className="p-6">
          {renderContent()}
        </main>
      </div>
    </div>
  );
};

export default App;