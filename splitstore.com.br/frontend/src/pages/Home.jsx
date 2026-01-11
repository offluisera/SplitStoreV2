import { useEffect, useState } from 'react';
import { ArrowRight, PlayCircle, Zap, CreditCard, BarChart3, ShieldCheck, Palette, Headset, Star, Check, Menu, Instagram, Twitter, Youtube, ShoppingCart } from 'lucide-react';
import AOS from 'aos';
import 'aos/dist/aos.css';
import axios from 'axios';

const Home = () => {
  const [scrolled, setScrolled] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [stats, setStats] = useState({
    lojas_ativas: 250,
    faturamento_total: 2000000,
    uptime: 99.9,
    total_clientes: 300
  });
  const [feedbacks, setFeedbacks] = useState([]);
  const [servidores, setServidores] = useState([]);
  const [loading, setLoading] = useState(true);

  // Função para formatar dinheiro
  const formatMoney = (value) => {
    if (value >= 1000000) {
      return `R$ ${(value / 1000000).toFixed(1)}M+`;
    } else if (value >= 1000) {
      return `R$ ${(value / 1000).toFixed(0)}K+`;
    }
    return `R$ ${value.toFixed(2)}`;
  };

  // Buscar dados da API
  useEffect(() => {
    const fetchData = async () => {
      try {
        // Buscar estatísticas
        const statsResponse = await axios.get('https://api.splitstore.com.br/stats.php');
        if (statsResponse.data) {
          setStats(statsResponse.data);
        }

        // Buscar feedbacks
        const feedbacksResponse = await axios.get('https://api.splitstore.com.br/feedbacks.php');
        if (feedbacksResponse.data && feedbacksResponse.data.length > 0) {
          setFeedbacks(feedbacksResponse.data);
        }

        // Buscar servidores parceiros
        const servidoresResponse = await axios.get('https://api.splitstore.com.br/servidores.php');
        if (servidoresResponse.data && servidoresResponse.data.length > 0) {
          setServidores(servidoresResponse.data);
        }

        setLoading(false);
      } catch (error) {
        console.log('Erro ao carregar dados:', error);
        setLoading(false);
        // Manter dados padrão em caso de erro
      }
    };

    fetchData();
  }, []);

  useEffect(() => {
    // Inicializar AOS
    AOS.init({
      duration: 1000,
      once: true,
      offset: 100,
      easing: 'ease-out-cubic'
    });

    // Particles.js - com delay para garantir que o canvas existe
    setTimeout(() => {
      if (window.particlesJS) {
        window.particlesJS("particles-js", {
          particles: {
            number: { value: 80, density: { enable: true, value_area: 800 } },
            color: { value: "#ef4444" },
            shape: { type: "circle" },
            opacity: {
              value: 0.15,
              random: true,
              anim: { enable: true, speed: 1, opacity_min: 0.05, sync: false }
            },
            size: {
              value: 3,
              random: true,
              anim: { enable: true, speed: 2, size_min: 0.5, sync: false }
            },
            line_linked: {
              enable: true,
              distance: 150,
              color: "#ef4444",
              opacity: 0.08,
              width: 1
            },
            move: {
              enable: true,
              speed: 1,
              direction: "none",
              random: true,
              out_mode: "out"
            }
          },
          interactivity: {
            detect_on: "canvas",
            events: {
              onhover: { enable: true, mode: "grab" },
              resize: true
            },
            modes: {
              grab: { distance: 140, line_linked: { opacity: 0.3 } }
            }
          },
          retina_detect: true
        });
      }
    }, 100);

    // Scroll handler
    const handleScroll = () => {
      setScrolled(window.pageYOffset > 100);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const scrollToSection = (id) => {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
      setMobileMenuOpen(false);
    }
  };

  const recursos = [
    {
      icon: Zap,
      title: 'Sincronização Real-Time',
      desc: 'Entrega automática via plugin com sincronização instantânea entre loja e servidor.',
      color: 'red'
    },
    {
      icon: CreditCard,
      title: 'Checkout Otimizado',
      desc: 'Processo de compra em 2 cliques com PIX, cartão e boleto integrados.',
      color: 'blue'
    },
    {
      icon: BarChart3,
      title: 'Analytics Avançado',
      desc: 'Dashboard completo com métricas de vendas, conversão e comportamento.',
      color: 'purple'
    },
    {
      icon: ShieldCheck,
      title: 'Anti-Fraude Nativo',
      desc: 'Proteção avançada contra chargebacks e transações fraudulentas.',
      color: 'green'
    },
    {
      icon: Palette,
      title: 'Design Customizável',
      desc: 'Interface moderna totalmente personalizável com sua identidade visual.',
      color: 'pink'
    },
    {
      icon: Headset,
      title: 'Suporte Especializado',
      desc: 'Time de especialistas disponível para resolver qualquer problema.',
      color: 'yellow'
    }
  ];

  // Feedbacks padrão caso a API não retorne dados
  const feedbacksPadrao = [
    {
      nome: 'Marcus Silva',
      cargo: 'Dono - RedeSky',
      texto: 'Triplicamos nossas vendas no primeiro mês. O sistema é absurdamente rápido e intuitivo. Melhor investimento que já fiz!',
      estrelas: 5,
      avatar: 'M',
      color: 'red'
    },
    {
      nome: 'Julia Santos',
      cargo: 'Admin - MegaCraft',
      texto: 'Suporte impecável! Qualquer dúvida é resolvida em minutos. A entrega automática funciona perfeitamente, zero problemas.',
      estrelas: 5,
      avatar: 'J',
      color: 'blue'
    },
    {
      nome: 'Rafael Costa',
      cargo: 'Owner - VortexPvP',
      texto: 'Migrei de outra plataforma e me arrependo de não ter conhecido antes. Dashboard completo, relatórios detalhados, tudo perfeito!',
      estrelas: 5,
      avatar: 'R',
      color: 'purple'
    }
  ];

  const planos = [
    {
      nome: 'Starter',
      preco: '14,99',
      desc: 'Perfeito para começar',
      destaque: false,
      features: [
        '1 Servidor Minecraft',
        'Checkout Responsivo',
        'Suporte via Ticket',
        'Plugin de Entrega'
      ]
    },
    {
      nome: 'Enterprise',
      preco: '25,99',
      desc: 'Para redes sérias',
      destaque: true,
      features: [
        '5 Servidores',
        'Checkout Customizável',
        'Suporte Prioritário 24/7',
        'Analytics Avançado',
        'API de Integração'
      ]
    },
    {
      nome: 'Gerencial',
      preco: '39,99',
      desc: 'Soluções enterprise',
      destaque: false,
      features: [
        'Servidores Ilimitados',
        'Whitelabel Completo',
        'Gerente de Contas',
        'Integrações Custom'
      ]
    }
  ];

  // Servidores padrão caso a API não retorne dados
  const servidoresPadrao = [
    { nome: 'RedeSky', sigla: 'RS', color: 'red' },
    { nome: 'MegaCraft', sigla: 'MC', color: 'blue' },
    { nome: 'VortexPvP', sigla: 'VP', color: 'purple' },
    { nome: 'HyperCraft', sigla: 'HC', color: 'green' },
    { nome: 'NexusRP', sigla: 'NX', color: 'yellow' }
  ];

  return (
    <div className="relative">
      {/* Particles Background */}
      <div id="particles-js" className="fixed w-full h-full top-0 left-0 z-[1] pointer-events-none"></div>

      <div className="relative z-10">
        {/* Navbar */}
        <nav className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
          scrolled ? 'bg-black/95 shadow-[0_8px_32px_rgba(0,0,0,0.4)]' : 'bg-black/85'
        } backdrop-blur-[20px] border-b border-white/[0.03]`}>
          <div className="max-w-7xl mx-auto px-6 lg:px-8">
            <div className="flex justify-between items-center h-20">
              {/* Logo */}
              <button onClick={() => scrollToSection('inicio')} className="flex items-center gap-3">
                <div className="w-10 h-10 bg-gradient-to-br from-red-600 to-red-900 rounded-xl flex items-center justify-center font-black shadow-lg shadow-red-900/40">
                  S
                </div>
                <span className="text-xl font-black tracking-tighter uppercase">
                  Split<span className="text-red-600">Store</span>
                </span>
              </button>

              {/* Desktop Menu */}
              <div className="hidden md:flex items-center gap-10">
                {['inicio', 'recursos', 'feedbacks', 'planos', 'servidores'].map((item) => (
                  <button
                    key={item}
                    onClick={() => scrollToSection(item)}
                    className="text-zinc-400 hover:text-white text-xs font-bold uppercase tracking-wider transition-colors"
                  >
                    {item.charAt(0).toUpperCase() + item.slice(1)}
                  </button>
                ))}
              </div>

              {/* CTA */}
              <div className="flex items-center gap-4">
                <a href="https://auth.splitstore.com.br" className="hidden md:block text-zinc-400 hover:text-white text-xs font-bold uppercase tracking-wider transition-colors">
                  Login
                </a>
                <button onClick={() => scrollToSection('planos')} className="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition-all hover:scale-105 active:scale-95 shadow-[0_0_40px_rgba(220,38,38,0.3)] hover:shadow-[0_0_60px_rgba(220,38,38,0.5)]">
                  Começar Agora
                </button>
                
                {/* Mobile Menu Button */}
                <button onClick={() => setMobileMenuOpen(!mobileMenuOpen)} className="md:hidden text-white">
                  <Menu className="w-6 h-6" />
                </button>
              </div>
            </div>

            {/* Mobile Menu */}
            <div className={`md:hidden overflow-hidden transition-all duration-300 border-t border-white/5 ${
              mobileMenuOpen ? 'max-h-[500px] mt-4' : 'max-h-0'
            }`}>
              <div className="py-4 space-y-4">
                {['inicio', 'recursos', 'feedbacks', 'planos', 'servidores'].map((item) => (
                  <button
                    key={item}
                    onClick={() => scrollToSection(item)}
                    className="block w-full text-left text-zinc-400 hover:text-white text-sm font-bold uppercase tracking-wider transition-colors"
                  >
                    {item.charAt(0).toUpperCase() + item.slice(1)}
                  </button>
                ))}
                <a href="https://auth.splitstore.com.br" className="block text-zinc-400 hover:text-white text-sm font-bold uppercase tracking-wider transition-colors">
                  Login
                </a>
              </div>
            </div>
          </div>
        </nav>

        {/* Hero Section */}
        <section id="inicio" className="relative min-h-screen flex items-center pt-20">
          <div className="absolute inset-0 bg-[radial-gradient(circle_at_center,_rgba(220,38,38,0.1)_0%,_transparent_70%)]"></div>
          
          <div className="relative max-w-7xl mx-auto px-6 lg:px-8 py-24">
            <div className="grid lg:grid-cols-2 gap-16 items-center">
              
              <div data-aos="fade-right">
                <div className="inline-flex items-center gap-2 bg-gradient-to-r from-red-600/20 to-red-900/10 border border-red-600/30 backdrop-blur-[10px] px-4 py-2 rounded-full mb-8">
                  <div className="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                  <span className="text-red-500 text-xs font-black uppercase tracking-wider">
                    Sistema V3.0 • Nova Geração
                  </span>
                </div>

                <h1 className="text-5xl md:text-7xl font-black uppercase tracking-tighter leading-[0.95] mb-6">
                  <span className="bg-gradient-to-br from-white to-zinc-400 bg-clip-text text-transparent">
                    A Revolução<br/>das Lojas
                  </span>
                  <br/>
                  <span className="bg-gradient-to-br from-red-600 to-red-800 bg-clip-text text-transparent">
                    Minecraft
                  </span>
                </h1>

                <p className="text-zinc-400 text-lg leading-relaxed mb-10 max-w-xl font-light">
                  Sistema completo de vendas com entrega automatizada, checkout premium e sincronização em tempo real. 
                  <span className="text-white font-semibold"> Transforme seu servidor em uma máquina de vendas.</span>
                </p>

                <div className="flex flex-col sm:flex-row gap-4 mb-12">
                  <button onClick={() => scrollToSection('planos')} className="group bg-red-600 hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-black text-sm uppercase tracking-wider shadow-[0_0_40px_rgba(220,38,38,0.3)] hover:shadow-[0_0_60px_rgba(220,38,38,0.5)] transition-all hover:scale-105 active:scale-95 flex items-center justify-center gap-3">
                    Criar Minha Loja
                    <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                  </button>
                  <button onClick={() => scrollToSection('recursos')} className="bg-gradient-to-br from-white/[0.02] to-white/[0.01] backdrop-blur-[20px] border border-white/5 hover:border-red-600/30 text-white px-8 py-4 rounded-2xl font-bold text-sm uppercase tracking-wider transition-all hover:scale-105 flex items-center justify-center gap-3">
                    <PlayCircle className="w-4 h-4" />
                    Ver Demonstração
                  </button>
                </div>

                <div className="grid grid-cols-3 gap-8">
                  <div>
                    <div className="text-3xl font-black text-white mb-1">{stats.lojas_ativas}+</div>
                    <div className="text-xs text-zinc-600 font-bold uppercase tracking-wider">Lojas Ativas</div>
                  </div>
                  <div>
                    <div className="text-3xl font-black text-white mb-1">{formatMoney(stats.faturamento_total)}</div>
                    <div className="text-xs text-zinc-600 font-bold uppercase tracking-wider">Processado</div>
                  </div>
                  <div>
                    <div className="text-3xl font-black text-white mb-1">{stats.uptime}%</div>
                    <div className="text-xs text-zinc-600 font-bold uppercase tracking-wider">Uptime</div>
                  </div>
                </div>
              </div>

              <div className="relative" data-aos="fade-left" data-aos-delay="200">
                <div className="absolute -inset-20 bg-red-600/20 blur-[120px] rounded-full animate-pulse"></div>
                
                <div className="relative bg-gradient-to-br from-white/[0.02] to-white/[0.01] backdrop-blur-[20px] border-2 border-white/10 hover:border-red-600/30 rounded-3xl p-8 transition-all hover:-translate-y-2 hover:shadow-[0_20px_60px_rgba(220,38,38,0.15)]">
                  <div className="aspect-video bg-gradient-to-br from-zinc-900 to-black rounded-2xl flex items-center justify-center border border-white/5 relative overflow-hidden">
                    {/* Grid pattern background */}
                    <div className="absolute inset-0 opacity-10">
                      <div className="absolute inset-0" style={{backgroundImage: 'linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px)', backgroundSize: '50px 50px'}}></div>
                    </div>
                    
                    {/* Icon centralizado */}
                    <div className="relative text-center z-10">
                      <ShoppingCart className="w-20 h-20 text-red-600/30 mx-auto mb-4" />
                      <span className="text-zinc-700 font-black text-2xl uppercase tracking-wider">Interface Premium</span>
                    </div>
                  </div>
                  
                  {/* Cards flutuantes */}
                  <div className="absolute -left-4 top-1/4 bg-gradient-to-br from-white/[0.05] to-white/[0.02] backdrop-blur-[20px] border border-red-600/20 px-4 py-3 rounded-xl shadow-xl">
                    <div className="flex items-center gap-3">
                      <Zap className="w-5 h-5 text-red-600" />
                      <div>
                        <div className="text-xs font-black text-white">Entrega Instantânea</div>
                        <div className="text-[10px] text-zinc-600">Em &lt; 3 segundos</div>
                      </div>
                    </div>
                  </div>

                  <div className="absolute -right-4 bottom-1/4 bg-gradient-to-br from-white/[0.05] to-white/[0.02] backdrop-blur-[20px] border border-green-600/20 px-4 py-3 rounded-xl shadow-xl">
                    <div className="flex items-center gap-3">
                      <ShieldCheck className="w-5 h-5 text-green-500" />
                      <div>
                        <div className="text-xs font-black text-white">100% Seguro</div>
                        <div className="text-[10px] text-zinc-600">Anti-fraude</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Recursos */}
        <section id="recursos" className="relative py-32 bg-zinc-950/50">
          <div className="max-w-7xl mx-auto px-6 lg:px-8">
            
            <div className="text-center mb-20" data-aos="fade-up">
              <div className="inline-block text-red-600 text-xs font-black uppercase tracking-[0.3em] mb-4 bg-red-600/10 px-4 py-2 rounded-full">
                Tecnologia de Ponta
              </div>
              <h2 className="text-5xl font-black uppercase tracking-tighter mb-4">
                Recursos que <span className="text-red-600">Dominam</span>
              </h2>
              <p className="text-zinc-500 text-lg max-w-2xl mx-auto font-light">
                Cada detalhe foi pensado para maximizar suas vendas
              </p>
            </div>

            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
              {recursos.map((recurso, i) => {
                const Icon = recurso.icon;
                const colorClasses = {
                  red: 'bg-red-600/10 text-red-600 hover:border-red-600/30',
                  blue: 'bg-blue-600/10 text-blue-600 hover:border-blue-600/30',
                  purple: 'bg-purple-600/10 text-purple-600 hover:border-purple-600/30',
                  green: 'bg-green-600/10 text-green-600 hover:border-green-600/30',
                  pink: 'bg-pink-600/10 text-pink-600 hover:border-pink-600/30',
                  yellow: 'bg-yellow-600/10 text-yellow-600 hover:border-yellow-600/30'
                };
                const colorClass = colorClasses[recurso.color] || colorClasses.red;
                
                return (
                  <div key={i} className="group" data-aos="fade-up" data-aos-delay={i * 100}>
                    <div className={`bg-gradient-to-br from-white/[0.02] to-white/[0.01] backdrop-blur-[20px] border border-white/5 ${colorClass.split(' ')[2]} p-8 rounded-3xl h-full transition-all duration-400 hover:-translate-y-2 hover:shadow-[0_20px_60px_rgba(220,38,38,0.15)]`}>
                      <div className={`w-14 h-14 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform ${colorClass.split(' ')[0]} ${colorClass.split(' ')[1]}`}>
                        <Icon className="w-7 h-7" />
                      </div>
                      <h3 className="text-xl font-black uppercase mb-3 tracking-tight">{recurso.title}</h3>
                      <p className="text-zinc-500 leading-relaxed text-sm">{recurso.desc}</p>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </section>

        {/* Feedbacks */}
        <section id="feedbacks" className="relative py-32">
          <div className="max-w-7xl mx-auto px-6 lg:px-8">
            
            <div className="text-center mb-20" data-aos="fade-up">
              <div className="inline-block text-red-600 text-xs font-black uppercase tracking-[0.3em] mb-4 bg-red-600/10 px-4 py-2 rounded-full">
                Depoimentos
              </div>
              <h2 className="text-5xl font-black uppercase tracking-tighter mb-4">
                Quem Usa, <span className="text-red-600">Aprova</span>
              </h2>
            </div>

            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
              {(feedbacks.length > 0 ? feedbacks : feedbacksPadrao).map((feedback, i) => {
                const colorClasses = {
                  red: 'from-red-600 to-red-900 border-red-600/20',
                  blue: 'from-blue-600 to-blue-900 border-blue-600/20',
                  purple: 'from-purple-600 to-purple-900 border-purple-600/20'
                };
                const colorClass = colorClasses[feedback.color] || colorClasses.red;
                
                return (
                  <div key={i} className="group" data-aos="fade-up" data-aos-delay={i * 100}>
                    <div className="bg-gradient-to-br from-white/[0.02] to-white/[0.01] backdrop-blur-[20px] border border-white/5 hover:border-red-600/20 p-8 rounded-3xl h-full flex flex-col transition-all duration-400 hover:-translate-y-2">
                      <div className="flex gap-1 mb-6">
                        {[...Array(5)].map((_, j) => (
                          <Star key={j} className="w-4 h-4 fill-red-600 text-red-600" />
                        ))}
                      </div>
                      <blockquote className="text-zinc-400 leading-relaxed mb-8 flex-1 italic">
                        "{feedback.texto}"
                      </blockquote>
                      <div className="flex items-center gap-4 pt-6 border-t border-white/5">
                        <div className={`w-12 h-12 bg-gradient-to-br ${colorClass.split(' ')[0]} ${colorClass.split(' ')[1]} rounded-full flex items-center justify-center font-black text-white border-2 ${colorClass.split(' ')[2]}`}>
                          {feedback.avatar}
                        </div>
                        <div>
                          <h4 className="font-black uppercase text-sm tracking-tight">{feedback.nome}</h4>
                          <p className="text-xs text-zinc-600 font-bold uppercase tracking-wider">{feedback.cargo}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </section>

        {/* Planos */}
        <section id="planos" className="relative py-32 bg-zinc-950/50">
          <div className="max-w-7xl mx-auto px-6 lg:px-8">
            
            <div className="text-center mb-20" data-aos="fade-up">
              <div className="inline-block text-red-600 text-xs font-black uppercase tracking-[0.3em] mb-4 bg-red-600/10 px-4 py-2 rounded-full">
                Investimento
              </div>
              <h2 className="text-5xl font-black uppercase tracking-tighter mb-4">
                Escolha seu <span className="text-red-600">Plano</span>
              </h2>
            </div>

            <div className="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
              {planos.map((plano, i) => (
                <div key={i} className="group relative" data-aos="fade-up" data-aos-delay={i * 100}>
                  {plano.destaque && (
                    <>
                      <div className="absolute -top-4 left-1/2 -translate-x-1/2 z-20">
                        <div className="bg-red-600 text-white text-[10px] font-black uppercase px-6 py-2 rounded-full shadow-lg shadow-red-600/50">
                          Mais Popular
                        </div>
                      </div>
                      <div className="absolute -inset-1 bg-red-600/20 rounded-[2rem] blur-2xl"></div>
                    </>
                  )}
                  <div className={`relative bg-gradient-to-br from-white/[0.02] to-white/[0.01] backdrop-blur-[20px] border ${plano.destaque ? 'border-red-600/50' : 'border-white/5'} p-10 rounded-[2rem] h-full flex flex-col transition-all duration-400 hover:-translate-y-2`}>
                    <div className="mb-8">
                      <h3 className="text-zinc-400 text-xs font-black uppercase tracking-[0.3em] mb-2">{plano.nome}</h3>
                      <p className="text-zinc-600 text-sm mb-6">{plano.desc}</p>
                      <div className="flex items-baseline gap-2">
                        <span className="text-zinc-500 text-xl">R$</span>
                        <span className="text-6xl font-black tracking-tighter">{plano.preco}</span>
                        <span className="text-zinc-600 text-sm">/mês</span>
                      </div>
                    </div>
                    <ul className="space-y-4 mb-10 flex-1">
                      {plano.features.map((feature, j) => (
                        <li key={j} className="flex items-center gap-3 text-zinc-400 text-sm">
                          <div className="w-5 h-5 bg-red-600/10 rounded-full flex items-center justify-center flex-shrink-0">
                            <Check className="w-3 h-3 text-red-600" />
                          </div>
                          {feature}
                        </li>
                      ))}
                    </ul>
                    <a href="https://auth.splitstore.com.br/register" className={`block w-full py-4 rounded-xl font-black text-sm uppercase tracking-wider text-center transition-all ${plano.destaque ? 'bg-red-600 text-white hover:bg-red-700 shadow-[0_0_40px_rgba(220,38,38,0.3)] hover:shadow-[0_0_60px_rgba(220,38,38,0.5)]' : 'bg-white text-black hover:bg-zinc-200'}`}>
                      Assinar Agora
                    </a>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Servidores */}
        <section id="servidores" className="relative py-32">
          <div className="max-w-7xl mx-auto px-6 lg:px-8">
            
            <div className="text-center mb-20" data-aos="fade-up">
              <div className="inline-block text-red-600 text-xs font-black uppercase tracking-[0.3em] mb-4 bg-red-600/10 px-4 py-2 rounded-full">
                Confiança
              </div>
              <h2 className="text-5xl font-black uppercase tracking-tighter mb-4">
                Redes que <span className="text-red-600">Confiam</span>
              </h2>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
              {(servidores.length > 0 ? servidores : servidoresPadrao).map((servidor, i) => {
                const colorClasses = {
                  red: 'from-red-600 to-red-900',
                  blue: 'from-blue-600 to-blue-900',
                  purple: 'from-purple-600 to-purple-900',
                  green: 'from-green-600 to-green-900',
                  yellow: 'from-yellow-600 to-yellow-900'
                };
                const colorClass = colorClasses[servidor.color] || colorClasses.red;
                
                return (
                  <div key={i} className="group" data-aos="zoom-in" data-aos-delay={i * 50}>
                    <div className="bg-gradient-to-br from-white/[0.02] to-white/[0.01] backdrop-blur-[20px] border border-white/5 hover:border-red-600/30 aspect-square rounded-2xl p-6 flex flex-col items-center justify-center grayscale opacity-40 hover:grayscale-0 hover:opacity-100 transition-all duration-500 hover:-translate-y-2">
                      <div className={`w-20 h-20 bg-gradient-to-br ${colorClass} rounded-2xl flex items-center justify-center font-black text-white text-2xl mb-4`}>
                        {servidor.sigla}
                      </div>
                      <span className="text-[10px] font-bold uppercase tracking-wider text-zinc-600 group-hover:text-red-600 transition-colors text-center">
                        {servidor.nome}
                      </span>
                    </div>
                  </div>
                );
              })}
            </div>

            <div className="text-center mt-16" data-aos="fade-up">
              <p className="text-zinc-600 text-sm mb-4">Sua rede também pode estar aqui</p>
              <button onClick={() => scrollToSection('planos')} className="text-red-600 font-black uppercase text-xs tracking-widest hover:text-red-500 transition-colors inline-flex items-center gap-2">
                Seja um Parceiro
                <ArrowRight className="w-4 h-4" />
              </button>
            </div>
          </div>
        </section>

        {/* CTA Final */}
        <section className="relative py-32 bg-zinc-950/50">
          <div className="max-w-5xl mx-auto px-6 lg:px-8">
            <div className="bg-gradient-to-br from-white/[0.02] to-white/[0.01] backdrop-blur-[20px] border border-red-600/20 p-16 rounded-[3rem] text-center relative overflow-hidden" data-aos="zoom-in">
              
              <div className="absolute inset-0 opacity-5">
                <div className="absolute inset-0" style={{backgroundImage: 'radial-gradient(circle, #ef4444 1px, transparent 1px)', backgroundSize: '40px 40px'}}></div>
              </div>

              <div className="relative z-10">
                <h2 className="text-5xl font-black uppercase tracking-tighter mb-6">
                  Pronto para <span className="text-red-600">Decolar?</span>
                </h2>
                <p className="text-zinc-400 text-lg mb-10 max-w-2xl mx-auto">
                  Junte-se a centenas de servidores que já transformaram suas vendas com o SplitStore. 
                  Comece gratuitamente por 7 dias.
                </p>
                
                <div className="flex flex-col sm:flex-row gap-4 justify-center">
                  <button onClick={() => scrollToSection('planos')} className="bg-red-600 hover:bg-red-700 text-white px-10 py-5 rounded-2xl font-black uppercase tracking-wider shadow-[0_0_40px_rgba(220,38,38,0.3)] hover:shadow-[0_0_60px_rgba(220,38,38,0.5)] transition-all hover:scale-105">
                    Começar Agora - Grátis
                  </button>
                  <button onClick={() => scrollToSection('recursos')} className="bg-gradient-to-br from-white/[0.02] to-white/[0.01] backdrop-blur-[20px] border border-white/10 hover:border-red-600/30 text-white px-10 py-5 rounded-2xl font-bold uppercase tracking-wider transition-all">
                    Ver Mais Recursos
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Footer */}
        <footer className="relative border-t border-white/5 bg-black py-16">
          <div className="max-w-7xl mx-auto px-6 lg:px-8">
            
            <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
              
              <div className="lg:col-span-2">
                <div className="flex items-center gap-3 mb-6">
                  <div className="w-10 h-10 bg-gradient-to-br from-red-600 to-red-900 rounded-xl flex items-center justify-center font-black shadow-lg">
                    S
                  </div>
                  <span className="text-xl font-black tracking-tighter uppercase">
                    Split<span className="text-red-600">Store</span>
                  </span>
                </div>
                <p className="text-zinc-500 leading-relaxed mb-6 max-w-sm">
                  A plataforma mais completa para vendas em servidores Minecraft. 
                  Tecnologia brasileira, suporte em português.
                </p>
                
                <div className="flex gap-3">
                  <a href="#" className="w-10 h-10 bg-gradient-to-br from-white/[0.02] to-white/[0.01] backdrop-blur-[20px] border border-white/5 rounded-xl flex items-center justify-center hover:border-red-600/50 transition-all text-zinc-400 hover:text-red-600">
                    <Instagram className="w-4 h-4" />
                  </a>
                  <a href="#" className="w-10 h-10 bg-gradient-to-br from-white/[0.02] to-white/[0.01] backdrop-blur-[20px] border border-white/5 rounded-xl flex items-center justify-center hover:border-red-600/50 transition-all text-zinc-400 hover:text-red-600">
                    <Twitter className="w-4 h-4" />
                  </a>
                  <a href="#" className="w-10 h-10 bg-gradient-to-br from-white/[0.02] to-white/[0.01] backdrop-blur-[20px] border border-white/5 rounded-xl flex items-center justify-center hover:border-red-600/50 transition-all text-zinc-400 hover:text-red-600">
                    <Youtube className="w-4 h-4" />
                  </a>
                </div>
              </div>

              <div>
                <h4 className="text-white text-xs font-black uppercase tracking-[0.3em] mb-6">Produto</h4>
                <ul className="space-y-3 text-sm text-zinc-500">
                  <li><button onClick={() => scrollToSection('recursos')} className="hover:text-white transition-colors">Recursos</button></li>
                  <li><button onClick={() => scrollToSection('planos')} className="hover:text-white transition-colors">Planos</button></li>
                  <li><button onClick={() => scrollToSection('servidores')} className="hover:text-white transition-colors">Servidores</button></li>
                </ul>
              </div>

              <div>
                <h4 className="text-white text-xs font-black uppercase tracking-[0.3em] mb-6">Suporte</h4>
                <ul className="space-y-3 text-sm text-zinc-500">
                  <li><a href="#" className="hover:text-white transition-colors">Documentação</a></li>
                  <li><button onClick={() => scrollToSection('feedbacks')} className="hover:text-white transition-colors">Feedbacks</button></li>
                  <li><a href="#" className="hover:text-white transition-colors">Contato</a></li>
                </ul>
              </div>
            </div>

            <div className="pt-8 border-t border-white/5 text-center">
              <p className="text-zinc-700 text-xs font-bold uppercase tracking-wider mb-2">
                © 2026 - SplitStore faz parte do GrupoSplit
              </p>
              <p className="text-zinc-800 text-[10px] font-semibold uppercase tracking-wider">
                Nenhum vínculo com Mojang e Microsoft
              </p>
            </div>
          </div>
        </footer>

      </div>
    </div>
  );
};

export default Home;