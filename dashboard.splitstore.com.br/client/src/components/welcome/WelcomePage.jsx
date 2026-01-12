// ============================================
// PASSO 1: Criar o arquivo do componente
// ============================================
// dashboard.splitstore.com.br/client/src/components/welcome/WelcomePage.jsx

import { useState, useEffect } from 'react';
import { 
  CheckCircle, 
  Sparkles, 
  Rocket, 
  Store, 
  Settings, 
  TrendingUp,
  ChevronRight,
  Copy,
  Check,
  ExternalLink,
  Zap,
  Shield,
  CreditCard,
  Users,
  Package,
  BarChart3,
  Palette,
  Server
} from 'lucide-react';

const WelcomePage = ({ storeData, onContinue }) => {
  const [step, setStep] = useState('celebrating');
  const [copied, setCopied] = useState(false);
  const [selectedSetupCards, setSelectedSetupCards] = useState([]);
  const [confettiVisible, setConfettiVisible] = useState(true);

  useEffect(() => {
    const timer = setTimeout(() => {
      setConfettiVisible(false);
    }, 5000);
    return () => clearTimeout(timer);
  }, []);

  const storeUrl = `https://${storeData.slug}.splitstore.com.br`;

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const setupSteps = [
    {
      id: 'products',
      icon: <Package className="w-8 h-8" />,
      title: 'Adicionar Produtos',
      description: 'Configure seus primeiros produtos e categorias',
      time: '5 min',
      color: 'from-blue-600/20 to-blue-800/20',
      borderColor: 'border-blue-600/30'
    },
    {
      id: 'design',
      icon: <Palette className="w-8 h-8" />,
      title: 'Personalizar Visual',
      description: 'Customize cores, logo e tema da sua loja',
      time: '10 min',
      color: 'from-purple-600/20 to-purple-800/20',
      borderColor: 'border-purple-600/30'
    },
    {
      id: 'payment',
      icon: <CreditCard className="w-8 h-8" />,
      title: 'Configurar Pagamentos',
      description: 'Conecte suas contas de pagamento',
      time: '3 min',
      color: 'from-green-600/20 to-green-800/20',
      borderColor: 'border-green-600/30'
    },
    {
      id: 'minecraft',
      icon: <Server className="w-8 h-8" />,
      title: 'Conectar Servidor',
      description: 'Integre seu servidor Minecraft',
      time: '5 min',
      color: 'from-orange-600/20 to-orange-800/20',
      borderColor: 'border-orange-600/30'
    }
  ];

  const features = [
    { icon: <Zap className="w-5 h-5" />, text: 'Checkout otimizado para conversão' },
    { icon: <Shield className="w-5 h-5" />, text: 'Segurança e proteção anti-fraude' },
    { icon: <TrendingUp className="w-5 h-5" />, text: 'Analytics e relatórios em tempo real' },
    { icon: <Users className="w-5 h-5" />, text: 'Gestão completa de clientes' }
  ];

  // CELEBRATING STEP
  if (step === 'celebrating') {
    return (
      <div className="min-h-screen bg-black text-white flex items-center justify-center p-4 relative overflow-hidden">
        {confettiVisible && (
          <div className="absolute inset-0 pointer-events-none overflow-hidden">
            {[...Array(50)].map((_, i) => (
              <div
                key={i}
                className="absolute animate-confetti"
                style={{
                  left: `${Math.random() * 100}%`,
                  top: '-10%',
                  animationDelay: `${Math.random() * 3}s`,
                  animationDuration: `${3 + Math.random() * 2}s`
                }}
              >
                <div
                  className="w-2 h-2 rounded-full"
                  style={{
                    backgroundColor: ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'][Math.floor(Math.random() * 5)]
                  }}
                />
              </div>
            ))}
          </div>
        )}

        <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,_rgba(239,68,68,0.15)_0%,_transparent_70%)]" />
        
        <div className="relative z-10 max-w-4xl mx-auto text-center animate-fade-in">
          <div className="mb-8 inline-flex">
            <div className="relative">
              <div className="absolute inset-0 bg-green-600 rounded-full blur-3xl opacity-50 animate-pulse" />
              <div className="relative w-32 h-32 bg-gradient-to-br from-green-600 to-emerald-600 rounded-full flex items-center justify-center shadow-2xl">
                <CheckCircle className="w-16 h-16 text-white" />
              </div>
            </div>
          </div>

          <div className="mb-12">
            <div className="flex items-center justify-center gap-3 mb-4">
              <Sparkles className="w-8 h-8 text-yellow-400 animate-bounce" />
              <h1 className="text-5xl md:text-7xl font-black">Parabéns! 🎉</h1>
              <Sparkles className="w-8 h-8 text-yellow-400 animate-bounce" />
            </div>
            <h2 className="text-2xl md:text-3xl font-bold text-zinc-300 mb-4">
              Sua loja <span className="text-red-600">{storeData.storeName}</span> está no ar!
            </h2>
            <p className="text-lg text-zinc-400 max-w-2xl mx-auto">
              Tudo pronto! Sua loja foi criada com sucesso e já está disponível para receber seus clientes.
            </p>
          </div>

          <div className="bg-gradient-to-br from-white/[0.08] to-white/[0.03] border border-white/10 rounded-2xl p-8 mb-8 backdrop-blur-sm">
            <div className="flex items-center justify-center gap-3 mb-4">
              <Store className="w-6 h-6 text-red-600" />
              <p className="text-sm font-bold uppercase tracking-wider text-zinc-400">URL da sua loja</p>
            </div>
            
            <div className="bg-black/50 rounded-xl p-4 mb-4">
              <div className="flex items-center justify-between gap-4 flex-wrap">
                <a 
                  href={storeUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-xl md:text-2xl font-bold text-red-600 hover:text-red-500 transition-colors flex items-center gap-2"
                >
                  {storeUrl}
                  <ExternalLink className="w-5 h-5" />
                </a>
                <button
                  onClick={() => copyToClipboard(storeUrl)}
                  className="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg font-bold transition-all flex items-center gap-2"
                >
                  {copied ? (
                    <>
                      <Check className="w-4 h-4" />
                      Copiado!
                    </>
                  ) : (
                    <>
                      <Copy className="w-4 h-4" />
                      Copiar
                    </>
                  )}
                </button>
              </div>
            </div>

            <p className="text-sm text-zinc-500">Compartilhe este link com seus clientes e comece a vender!</p>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
            {[
              { icon: <TrendingUp />, label: 'Plano', value: storeData.planName },
              { icon: <Package />, label: 'Produtos', value: 'Ilimitados' },
              { icon: <Users />, label: 'Clientes', value: 'Sem Limite' },
              { icon: <BarChart3 />, label: 'Analytics', value: 'Ativo' }
            ].map((stat, idx) => (
              <div 
                key={idx}
                className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-xl p-6"
              >
                <div className="w-12 h-12 bg-red-600/20 rounded-lg flex items-center justify-center mb-3 mx-auto">
                  <div className="text-red-600">{stat.icon}</div>
                </div>
                <p className="text-xs text-zinc-500 mb-1">{stat.label}</p>
                <p className="font-black text-lg">{stat.value}</p>
              </div>
            ))}
          </div>

          <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <button
              onClick={() => setStep('setup')}
              className="px-8 py-4 bg-red-600 hover:bg-red-700 rounded-xl font-black uppercase tracking-wider transition-all flex items-center gap-2 shadow-2xl shadow-red-600/50"
            >
              <Rocket className="w-5 h-5" />
              Configurar Minha Loja
              <ChevronRight className="w-5 h-5" />
            </button>
            
            <button
              onClick={() => onContinue()}
              className="px-8 py-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl font-bold transition-all flex items-center gap-2"
            >
              Ir para Dashboard
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>

          <div className="mt-12 pt-12 border-t border-white/10">
            <p className="text-sm font-bold uppercase tracking-wider text-zinc-400 mb-6">
              O que você já tem disponível:
            </p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {features.map((feature, idx) => (
                <div 
                  key={idx}
                  className="flex items-center gap-3 bg-gradient-to-r from-white/[0.05] to-transparent border border-white/10 rounded-lg p-4"
                >
                  <div className="w-10 h-10 bg-red-600/20 rounded-lg flex items-center justify-center flex-shrink-0">
                    <div className="text-red-600">{feature.icon}</div>
                  </div>
                  <p className="text-sm text-zinc-300">{feature.text}</p>
                </div>
              ))}
            </div>
          </div>
        </div>

        <style jsx>{`
          @keyframes confetti {
            0% {
              transform: translateY(0) rotateZ(0deg);
              opacity: 1;
            }
            100% {
              transform: translateY(100vh) rotateZ(360deg);
              opacity: 0;
            }
          }
          .animate-confetti {
            animation: confetti linear forwards;
          }
          .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
          }
          @keyframes fadeIn {
            from {
              opacity: 0;
              transform: translateY(20px);
            }
            to {
              opacity: 1;
              transform: translateY(0);
            }
          }
        `}</style>
      </div>
    );
  }

  // SETUP STEP
  if (step === 'setup') {
    return (
      <div className="min-h-screen bg-black text-white p-4">
        <div className="max-w-5xl mx-auto py-12">
          <button
            onClick={() => setStep('celebrating')}
            className="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors mb-8"
          >
            <ChevronRight className="w-4 h-4 rotate-180" />
            Voltar
          </button>

          <div className="text-center mb-12">
            <div className="inline-flex items-center gap-3 mb-4">
              <Settings className="w-8 h-8 text-red-600" />
              <h2 className="text-4xl font-black">Configuração Inicial</h2>
            </div>
            <p className="text-zinc-400 text-lg">
              Siga estes passos rápidos para deixar sua loja pronta para vender
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-6 mb-12">
            {setupSteps.map((step) => {
              const isSelected = selectedSetupCards.includes(step.id);
              
              return (
                <div
                  key={step.id}
                  onClick={() => {
                    if (isSelected) {
                      setSelectedSetupCards(prev => prev.filter(id => id !== step.id));
                    } else {
                      setSelectedSetupCards(prev => [...prev, step.id]);
                    }
                  }}
                  className={`cursor-pointer bg-gradient-to-br ${step.color} border ${step.borderColor} rounded-2xl p-6 transition-all hover:scale-105 ${
                    isSelected ? 'ring-2 ring-white/50 shadow-2xl' : ''
                  }`}
                >
                  <div className="flex items-start justify-between mb-4">
                    <div className={`w-16 h-16 rounded-xl flex items-center justify-center ${
                      isSelected ? 'bg-white/20' : 'bg-white/10'
                    }`}>
                      {step.icon}
                    </div>
                    {isSelected && (
                      <div className="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                        <Check className="w-5 h-5" />
                      </div>
                    )}
                  </div>

                  <h3 className="text-xl font-black mb-2">{step.title}</h3>
                  <p className="text-sm text-zinc-300 mb-4">{step.description}</p>

                  <div className="flex items-center justify-between">
                    <span className="text-xs bg-white/10 px-3 py-1 rounded-full">⏱️ {step.time}</span>
                    <button className={`px-4 py-2 rounded-lg font-bold text-sm transition-all ${
                      isSelected ? 'bg-white text-black' : 'bg-white/10 hover:bg-white/20'
                    }`}>
                      {isSelected ? 'Concluído' : 'Iniciar'}
                    </button>
                  </div>
                </div>
              );
            })}
          </div>

          <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-2xl p-8 mb-8">
            <div className="flex items-center justify-between mb-4">
              <span className="font-bold">Progresso da Configuração</span>
              <span className="text-2xl font-black text-red-600">
                {selectedSetupCards.length}/{setupSteps.length}
              </span>
            </div>
            <div className="h-3 bg-black/50 rounded-full overflow-hidden">
              <div 
                className="h-full bg-gradient-to-r from-red-600 to-red-800 transition-all duration-500 rounded-full"
                style={{ width: `${(selectedSetupCards.length / setupSteps.length) * 100}%` }}
              />
            </div>
          </div>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <button
              onClick={() => onContinue()}
              className="px-8 py-4 bg-red-600 hover:bg-red-700 rounded-xl font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2"
            >
              <Rocket className="w-5 h-5" />
              Ir para o Dashboard
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
    );
  }

  return null;
};

export default WelcomePage;