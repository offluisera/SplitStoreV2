// dashboard.splitstore.com.br/client/src/App.jsx
import { useState } from 'react';
import { ChevronRight, Check, Zap, Shield, TrendingUp, X, AlertCircle, Loader, CreditCard, Smartphone, FileText } from 'lucide-react';

// ============= COMPONENTE: PlanCard =============
const PlanCard = ({ plan, isPopular, isSelected, onSelect }) => {
  return (
    <div 
      onClick={() => onSelect(plan)}
      className={`relative rounded-3xl p-8 transition-all cursor-pointer ${
        isSelected 
          ? 'bg-gradient-to-br from-red-600 to-red-900 scale-105 shadow-2xl shadow-red-600/50' 
          : 'bg-gradient-to-br from-white/[0.05] to-white/[0.02] hover:from-white/[0.08] hover:to-white/[0.04]'
      } border ${isSelected ? 'border-red-500' : 'border-white/10'}`}
    >
      {isPopular && (
        <div className="absolute -top-4 left-1/2 -translate-x-1/2">
          <div className="bg-red-600 text-white text-xs font-black uppercase px-6 py-2 rounded-full shadow-lg">
            MAIS POPULAR
          </div>
        </div>
      )}

      {isSelected && (
        <div className="absolute -top-3 -right-3">
          <div className="bg-white text-red-600 rounded-full p-2 shadow-lg">
            <Check className="w-5 h-5" />
          </div>
        </div>
      )}

      <div className="text-center mb-6">
        <div className="inline-flex items-center gap-2 mb-4">
          {plan.icon}
          <h3 className="text-2xl font-black uppercase">{plan.name}</h3>
        </div>
        <p className={`text-sm ${isSelected ? 'text-red-100' : 'text-zinc-400'}`}>
          {plan.description}
        </p>
      </div>

      <div className="text-center mb-8">
        <div className="flex items-baseline justify-center gap-2">
          <span className="text-sm opacity-70">R$</span>
          <span className="text-6xl font-black">{plan.price}</span>
          <span className="text-sm opacity-70">/mês</span>
        </div>
      </div>

      <div className="space-y-3 mb-8">
        {plan.features.map((feature, idx) => (
          <div key={idx} className="flex items-start gap-3">
            <div className={`flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center mt-0.5 ${
              isSelected ? 'bg-white/20' : 'bg-red-600/20'
            }`}>
              <Check className={`w-3 h-3 ${isSelected ? 'text-white' : 'text-red-600'}`} />
            </div>
            <span className={`text-sm ${isSelected ? 'text-white' : 'text-zinc-300'}`}>
              {feature}
            </span>
          </div>
        ))}
      </div>

      {plan.highlight && (
        <div className={`p-4 rounded-xl mb-6 ${
          isSelected 
            ? 'bg-white/10 border border-white/20' 
            : 'bg-red-600/10 border border-red-600/20'
        }`}>
          <p className="text-xs font-semibold text-center">
            {plan.highlight}
          </p>
        </div>
      )}

      <button className={`w-full py-4 rounded-xl font-black uppercase tracking-wider transition-all ${
        isSelected
          ? 'bg-white text-red-600 hover:bg-red-50'
          : 'bg-red-600 text-white hover:bg-red-700'
      }`}>
        {isSelected ? 'Selecionado' : 'Selecionar Plano'}
      </button>
    </div>
  );
};

// ============= COMPONENTE: StoreSetupForm =============
const StoreSetupForm = ({ selectedPlan, onBack, onNext }) => {
  const [formData, setFormData] = useState({
    storeName: '',
    storeSlug: '',
    couponCode: ''
  });
  const [couponApplied, setCouponApplied] = useState(null);
  const [loading, setLoading] = useState(false);
  const [slugError, setSlugError] = useState('');
  const [couponError, setCouponError] = useState('');

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));

    if (name === 'storeName') {
      const slug = value
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
      setFormData(prev => ({ ...prev, storeSlug: slug }));
      setSlugError('');
    }
  };

  const handleApplyCoupon = async () => {
    if (!formData.couponCode) return;
    
    setLoading(true);
    setCouponError('');
    
    try {
      const response = await fetch('/api/checkout/validate-coupon', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: formData.couponCode })
      });

      const data = await response.json();
      
      if (response.ok && data.valid) {
        setCouponApplied({
          code: data.code,
          discount: data.discount_value,
          type: data.discount_type
        });
      } else {
        setCouponError(data.error || 'Cupom inválido');
      }
    } catch (error) {
      setCouponError('Erro ao validar cupom');
    } finally {
      setLoading(false);
    }
  };

  const calculateTotal = () => {
    let total = parseFloat(selectedPlan.price_numeric);
    
    if (couponApplied) {
      total = total * (1 - couponApplied.discount / 100);
    }
    
    return total.toFixed(2).replace('.', ',');
  };

  const handleSubmit = () => {
    if (!formData.storeName || !formData.storeSlug || slugError) {
      return;
    }

    onNext({
      ...formData,
      coupon: couponApplied,
      total: calculateTotal()
    });
  };

  return (
    <div className="max-w-2xl mx-auto animate-fade-in">
      <button
        onClick={onBack}
        className="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors mb-8"
      >
        <ChevronRight className="w-4 h-4 rotate-180" />
        Voltar aos planos
      </button>

      <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-8">
        <div className="text-center mb-8">
          <h2 className="text-3xl font-black mb-2">Configure sua loja</h2>
          <p className="text-zinc-400">
            Preencha os dados para criar sua loja no plano {selectedPlan.name}
          </p>
        </div>

        <div className="bg-red-600/10 border border-red-600/20 rounded-xl p-6 mb-8">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-zinc-400 mb-1">Plano selecionado</p>
              <p className="text-xl font-black">{selectedPlan.name}</p>
            </div>
            <div className="text-right">
              <p className="text-sm text-zinc-400 mb-1">Valor mensal</p>
              <p className="text-2xl font-black text-red-600">
                R$ {selectedPlan.price}
              </p>
            </div>
          </div>
        </div>

        <div className="space-y-6 mb-8">
          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">
              Nome da Loja *
            </label>
            <input
              type="text"
              name="storeName"
              value={formData.storeName}
              onChange={handleChange}
              className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3.5 focus:outline-none focus:border-red-600/50 transition-colors"
              placeholder="Minha Loja Incrível"
              required
            />
          </div>

          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">
              URL da Loja *
            </label>
            <div className="flex items-center gap-2">
              <input
                type="text"
                name="storeSlug"
                value={formData.storeSlug}
                onChange={handleChange}
                className={`flex-1 bg-white/5 border ${slugError ? 'border-red-500' : 'border-white/10'} rounded-xl px-4 py-3.5 focus:outline-none focus:border-red-600/50 transition-colors`}
                placeholder="minha-loja"
                required
              />
              <span className="text-zinc-500 text-sm whitespace-nowrap">.splitstore.com.br</span>
            </div>
            {slugError && (
              <div className="mt-2 flex items-center gap-2 text-red-500 text-sm">
                <AlertCircle className="w-4 h-4" />
                {slugError}
              </div>
            )}
          </div>

          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">
              Cupom de Desconto
            </label>
            <div className="flex gap-2">
              <input
                type="text"
                name="couponCode"
                value={formData.couponCode}
                onChange={handleChange}
                className="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3.5 focus:outline-none focus:border-red-600/50 transition-colors uppercase"
                placeholder="DIGITE SEU CUPOM"
                disabled={couponApplied}
              />
              {!couponApplied ? (
                <button
                  onClick={handleApplyCoupon}
                  disabled={loading || !formData.couponCode}
                  className="px-6 bg-red-600 hover:bg-red-700 disabled:bg-red-800 disabled:opacity-50 rounded-xl font-bold transition-all flex items-center gap-2"
                >
                  {loading ? <Loader className="w-4 h-4 animate-spin" /> : 'Aplicar'}
                </button>
              ) : (
                <button
                  onClick={() => {
                    setCouponApplied(null);
                    setFormData(prev => ({ ...prev, couponCode: '' }));
                  }}
                  className="px-6 bg-red-600/20 hover:bg-red-600/30 rounded-xl font-bold transition-all"
                >
                  <X className="w-5 h-5" />
                </button>
              )}
            </div>
            {couponApplied && (
              <div className="mt-2 flex items-center gap-2 text-green-500 text-sm">
                <Check className="w-4 h-4" />
                Cupom aplicado! {couponApplied.discount}% de desconto
              </div>
            )}
            {couponError && (
              <div className="mt-2 flex items-center gap-2 text-red-500 text-sm">
                <AlertCircle className="w-4 h-4" />
                {couponError}
              </div>
            )}
          </div>
        </div>

        <div className="bg-gradient-to-r from-red-600/20 to-red-900/20 border border-red-600/30 rounded-xl p-6 mb-8">
          <div className="flex items-center justify-between mb-4">
            <span className="text-zinc-400">Subtotal</span>
            <span className="font-semibold">R$ {selectedPlan.price}</span>
          </div>
          {couponApplied && (
            <div className="flex items-center justify-between mb-4 text-green-500">
              <span>Desconto ({couponApplied.discount}%)</span>
              <span className="font-semibold">
                - R$ {(parseFloat(selectedPlan.price_numeric) * couponApplied.discount / 100).toFixed(2).replace('.', ',')}
              </span>
            </div>
          )}
          <div className="border-t border-white/10 pt-4 flex items-center justify-between">
            <span className="text-xl font-bold">Total</span>
            <span className="text-3xl font-black text-red-600">
              R$ {calculateTotal()}
            </span>
          </div>
        </div>

        <button
          onClick={handleSubmit}
          disabled={!formData.storeName || !formData.storeSlug || !!slugError}
          className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed text-white py-4 rounded-xl font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2"
        >
          Prosseguir para Pagamento
          <ChevronRight className="w-5 h-5" />
        </button>
      </div>
    </div>
  );
};

// ============= COMPONENTE: PaymentMethodSelector =============
const PaymentMethodSelector = ({ selectedMethod, onSelect, onNext, onBack, selectedPlan, storeData }) => {
  const paymentMethods = [
    {
      id: 'pix',
      name: 'PIX',
      icon: <Smartphone className="w-8 h-8" />,
      description: 'Aprovação instantânea',
      available: true,
      recommended: true
    },
    {
      id: 'credit_card',
      name: 'Cartão de Crédito',
      icon: <CreditCard className="w-8 h-8" />,
      description: 'Em breve via Pagar.me',
      available: false
    },
    {
      id: 'boleto',
      name: 'Boleto',
      icon: <FileText className="w-8 h-8" />,
      description: 'Em breve via Pagar.me',
      available: false
    }
  ];

  return (
    <div className="max-w-2xl mx-auto animate-fade-in">
      <button
        onClick={onBack}
        className="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors mb-8"
      >
        <ChevronRight className="w-4 h-4 rotate-180" />
        Voltar
      </button>

      <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-8">
        <div className="text-center mb-8">
          <h2 className="text-3xl font-black mb-2">Forma de Pagamento</h2>
          <p className="text-zinc-400">
            Escolha como deseja pagar seu plano {selectedPlan.name}
          </p>
        </div>

        <div className="bg-red-600/10 border border-red-600/20 rounded-xl p-6 mb-8">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-zinc-400 mb-1">Loja</p>
              <p className="font-black">{storeData.storeName}</p>
              <p className="text-sm text-zinc-500">{storeData.storeSlug}.splitstore.com.br</p>
            </div>
            <div className="text-right">
              <p className="text-sm text-zinc-400 mb-1">Total</p>
              <p className="text-2xl font-black text-red-600">R$ {storeData.total}</p>
            </div>
          </div>
        </div>

        <div className="space-y-4 mb-8">
          {paymentMethods.map((method) => (
            <div
              key={method.id}
              onClick={() => method.available && onSelect(method.id)}
              className={`relative rounded-2xl p-6 transition-all cursor-pointer border-2 ${
                method.available
                  ? selectedMethod === method.id
                    ? 'bg-red-600/20 border-red-600'
                    : 'bg-white/5 border-white/10 hover:border-white/30'
                  : 'bg-white/[0.02] border-white/5 cursor-not-allowed opacity-50'
              }`}
            >
              {method.recommended && (
                <div className="absolute -top-3 left-6">
                  <div className="bg-green-600 text-white text-xs font-black uppercase px-4 py-1 rounded-full">
                    Recomendado
                  </div>
                </div>
              )}

              <div className="flex items-center gap-4">
                <div className={`flex-shrink-0 w-16 h-16 rounded-xl flex items-center justify-center ${
                  selectedMethod === method.id
                    ? 'bg-red-600/30 text-red-600'
                    : 'bg-white/5 text-zinc-400'
                }`}>
                  {method.icon}
                </div>
                
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-1">
                    <h3 className="text-xl font-black">{method.name}</h3>
                    {!method.available && (
                      <span className="text-xs bg-zinc-800 text-zinc-400 px-2 py-1 rounded-full">
                        Em breve
                      </span>
                    )}
                  </div>
                  <p className="text-sm text-zinc-400">{method.description}</p>
                </div>

                {method.available && selectedMethod === method.id && (
                  <Check className="w-6 h-6 text-red-600" />
                )}
              </div>
            </div>
          ))}
        </div>

        <button
          onClick={() => selectedMethod && onNext(selectedMethod)}
          disabled={!selectedMethod}
          className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed text-white py-4 rounded-xl font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2"
        >
          Finalizar Pedido
          <ChevronRight className="w-5 h-5" />
        </button>
      </div>
    </div>
  );
};

// ============= COMPONENTE: PaymentGateway =============
const PaymentGateway = ({ selectedPlan, storeData, paymentMethod, onBack }) => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const createCheckout = async () => {
    setLoading(true);
    setError('');

    try {
      const token = localStorage.getItem('auth_token');
      
      const response = await fetch('/api/checkout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          plan_id: selectedPlan.id,
          store_name: storeData.storeName,
          store_slug: storeData.storeSlug,
          coupon_code: storeData.coupon?.code || '',
          payment_method: paymentMethod
        })
      });

      const data = await response.json();

      if (response.ok && data.success) {
        if (data.payment_url) {
          window.location.href = data.payment_url;
        }
      } else {
        setError(data.error || 'Erro ao criar checkout');
      }
    } catch (err) {
      setError('Erro ao processar pagamento');
    } finally {
      setLoading(false);
    }
  };

  useState(() => {
    createCheckout();
  }, []);

  if (loading) {
    return (
      <div className="max-w-2xl mx-auto animate-fade-in">
        <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-12 text-center">
          <div className="w-20 h-20 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <Loader className="w-10 h-10 text-red-600 animate-spin" />
          </div>
          <h2 className="text-3xl font-black mb-4">Processando...</h2>
          <p className="text-zinc-400">Gerando link de pagamento</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-2xl mx-auto animate-fade-in">
        <button onClick={onBack} className="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors mb-8">
          <ChevronRight className="w-4 h-4 rotate-180" />
          Voltar
        </button>

        <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-12 text-center">
          <div className="w-20 h-20 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <X className="w-10 h-10 text-red-600" />
          </div>
          <h2 className="text-3xl font-black mb-4 text-red-600">Erro no Pagamento</h2>
          <p className="text-zinc-400 mb-8">{error}</p>
          <div className="flex gap-4 justify-center">
            <button onClick={onBack} className="px-8 py-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl font-bold transition-all">
              Voltar
            </button>
            <button onClick={createCheckout} className="px-8 py-3 bg-red-600 hover:bg-red-700 rounded-xl font-bold transition-all">
              Tentar Novamente
            </button>
          </div>
        </div>
      </div>
    );
  }

  return null;
};

// ============= COMPONENTE PRINCIPAL: App =============
const App = () => {
  const [step, setStep] = useState('plans');
  const [selectedPlan, setSelectedPlan] = useState(null);
  const [storeData, setStoreData] = useState(null);
  const [paymentMethod, setPaymentMethod] = useState('pix');

  const plans = [
    {
      id: 'starter',
      name: 'STARTER',
      description: 'Perfeito para começar',
      price: '14,99',
      price_numeric: 14.99,
      icon: <Zap className="w-6 h-6 text-red-600" />,
      features: [
        '1 Servidor Minecraft',
        'Checkout Responsivo',
        'Suporte via Ticket',
        'Plugin de Entrega'
      ]
    },
    {
      id: 'enterprise',
      name: 'ENTERPRISE',
      description: 'Para redes sérias',
      price: '25,99',
      price_numeric: 25.99,
      icon: <Shield className="w-6 h-6 text-red-600" />,
      features: [
        '5 Servidores',
        'Checkout Customizável',
        'Suporte Prioritário 24/7',
        'Analytics Avançado'
      ],
      highlight: '🔥 Mais escolhido',
      isPopular: true
    },
    {
      id: 'gerencial',
      name: 'GERENCIAL',
      description: 'Soluções enterprise',
      price: '39,99',
      price_numeric: 39.99,
      icon: <TrendingUp className="w-6 h-6 text-red-600" />,
      features: [
        'Servidores Ilimitados',
        'Whitelabel Completo',
        'Gerente de Contas',
        'Integrações Custom'
      ]
    }
  ];

  return (
    <div className="min-h-screen bg-black text-white p-4">
      <div id="particles-js" className="fixed inset-0 z-0"></div>
      <div className="fixed inset-0 bg-[radial-gradient(circle_at_center,_rgba(220,38,38,0.1)_0%,_transparent_70%)] z-0"></div>

      <div className="relative z-10 max-w-7xl mx-auto py-12">
        <div className="text-center mb-16">
          <div className="inline-flex items-center gap-3 mb-6">
            <div className="w-12 h-12 bg-gradient-to-br from-red-600 to-red-900 rounded-xl flex items-center justify-center font-black">
              S
            </div>
            <span className="text-2xl font-black tracking-tighter uppercase">
              Split<span className="text-red-600">Store</span>
            </span>
          </div>
          
          {step === 'plans' && (
            <>
              <h1 className="text-5xl font-black mb-4">
                Escolha seu <span className="text-red-600">Plano</span>
              </h1>
              <p className="text-zinc-400 text-lg">
                Comece agora e transforme suas vendas
              </p>
            </>
          )}
        </div>

        {step === 'plans' && (
          <div className="grid md:grid-cols-3 gap-8">
            {plans.map((plan) => (
              <PlanCard
                key={plan.id}
                plan={plan}
                isPopular={plan.isPopular}
                isSelected={selectedPlan?.id === plan.id}
                onSelect={(p) => {
                  setSelectedPlan(p);
                  setStep('setup');
                }}
              />
            ))}
          </div>
        )}

        {step === 'setup' && selectedPlan && (
          <StoreSetupForm
            selectedPlan={selectedPlan}
            onBack={() => setStep('plans')}
            onNext={(data) => {
              setStoreData(data);
              setStep('payment_method');
            }}
          />
        )}

        {step === 'payment_method' && (
          <PaymentMethodSelector
            selectedMethod={paymentMethod}
            onSelect={setPaymentMethod}
            selectedPlan={selectedPlan}
            storeData={storeData}
            onBack={() => setStep('setup')}
            onNext={(method) => {
              setPaymentMethod(method);
              setStep('checkout');
            }}
          />
        )}

        {step === 'checkout' && (
          <PaymentGateway
            selectedPlan={selectedPlan}
            storeData={storeData}
            paymentMethod={paymentMethod}
            onBack={() => setStep('payment_method')}
          />
        )}

        <div className="text-center mt-16">
          <p className="text-zinc-700 text-xs">
            © 2026 SplitStore • Todos os direitos reservados
          </p>
        </div>
      </div>
    </div>
  );
};

export default App;