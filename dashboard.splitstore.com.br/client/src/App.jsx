import { useState } from 'react';
import { ChevronRight, Check, Zap, Shield, TrendingUp, X, AlertCircle, Loader, Copy, Clock, CheckCircle, XCircle, CreditCard, Barcode } from 'lucide-react';

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
    customerCpf: '',
    couponCode: ''
  });
  const [couponApplied, setCouponApplied] = useState(null);
  const [loading, setLoading] = useState(false);
  const [slugError, setSlugError] = useState('');
  const [couponError, setCouponError] = useState('');

  const handleChange = (e) => {
    const { name, value } = e.target;
    
    if (name === 'customerCpf') {
      const cpfValue = value.replace(/\D/g, '');
      let formatted = cpfValue;
      
      if (cpfValue.length <= 11) {
        formatted = cpfValue
          .replace(/(\d{3})(\d)/, '$1.$2')
          .replace(/(\d{3})(\d)/, '$1.$2')
          .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
      }
      
      setFormData(prev => ({ ...prev, [name]: formatted }));
      return;
    }
    
    setFormData(prev => ({ ...prev, [name]: value }));

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
      console.error('Erro ao validar cupom:', error);
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
    if (!formData.storeName || !formData.storeSlug || !formData.customerCpf || slugError) {
      alert('Por favor, preencha todos os campos obrigatórios');
      return;
    }

    const cpfClean = formData.customerCpf.replace(/\D/g, '');
    if (cpfClean.length !== 11) {
      alert('CPF inválido. Digite 11 números.');
      return;
    }

    const submitData = {
      storeName: formData.storeName,
      storeSlug: formData.storeSlug,
      customerCpf: cpfClean,
      couponCode: formData.couponCode,
      coupon: couponApplied,
      total: calculateTotal()
    };
    
    onNext(submitData);
  };

  return (
    <div className="max-w-2xl mx-auto animate-fade-in">
      <button onClick={onBack} className="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors mb-8">
        <ChevronRight className="w-4 h-4 rotate-180" />
        Voltar aos planos
      </button>

      <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-8">
        <div className="text-center mb-8">
          <h2 className="text-3xl font-black mb-2">Configure sua loja</h2>
          <p className="text-zinc-400">Preencha os dados para criar sua loja no plano {selectedPlan.name}</p>
        </div>

        <div className="bg-red-600/10 border border-red-600/20 rounded-xl p-6 mb-8">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-zinc-400 mb-1">Plano selecionado</p>
              <p className="text-xl font-black">{selectedPlan.name}</p>
            </div>
            <div className="text-right">
              <p className="text-sm text-zinc-400 mb-1">Valor mensal</p>
              <p className="text-2xl font-black text-red-600">R$ {selectedPlan.price}</p>
            </div>
          </div>
        </div>

        <div className="space-y-6 mb-8">
          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Nome da Loja *</label>
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
            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">URL da Loja *</label>
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
            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">CPF do Titular *</label>
            <input
              type="text"
              name="customerCpf"
              value={formData.customerCpf}
              onChange={handleChange}
              maxLength="14"
              className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3.5 focus:outline-none focus:border-red-600/50 transition-colors"
              placeholder="000.000.000-00"
              required
            />
            <p className="mt-2 text-xs text-zinc-500">CPF necessário para geração do pagamento</p>
          </div>

          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-2">Cupom de Desconto</label>
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
            <span className="text-3xl font-black text-red-600">R$ {calculateTotal()}</span>
          </div>
        </div>

        <button
          onClick={handleSubmit}
          disabled={!formData.storeName || !formData.storeSlug || !formData.customerCpf || !!slugError}
          className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed text-white py-4 rounded-xl font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2"
        >
          Escolher Forma de Pagamento
          <ChevronRight className="w-5 h-5" />
        </button>
      </div>
    </div>
  );
};

// ============= COMPONENTE: PaymentMethodSelector =============
const PaymentMethodSelector = ({ amount, storeData, onSelect, onBack }) => {
  const [selectedMethod, setSelectedMethod] = useState(null);
  const [loading, setLoading] = useState(false);

  const paymentMethods = [
    {
      id: 'pix',
      name: 'PIX',
      description: 'Aprovação instantânea',
      icon: <Zap className="w-8 h-8" />,
      badge: 'Mais Rápido',
      badgeColor: 'bg-green-600',
      features: ['Aprovação imediata', 'Disponível 24/7', 'Sem taxas adicionais'],
      gradient: 'from-green-600/20 to-emerald-600/20',
      borderColor: 'border-green-600/30'
    },
    {
      id: 'credit_card',
      name: 'Cartão de Crédito',
      description: 'Parcelamento disponível',
      icon: <CreditCard className="w-8 h-8" />,
      badge: 'Parcelado',
      badgeColor: 'bg-blue-600',
      features: ['Até 12x sem juros', 'Aprovação em minutos', 'Todas as bandeiras'],
      gradient: 'from-blue-600/20 to-indigo-600/20',
      borderColor: 'border-blue-600/30'
    },
    {
      id: 'boleto',
      name: 'Boleto Bancário',
      description: 'Vencimento em 3 dias',
      icon: <Barcode className="w-8 h-8" />,
      badge: 'Tradicional',
      badgeColor: 'bg-orange-600',
      features: ['Pague em qualquer banco', 'Sem necessidade de cartão', 'Aprovação em até 2 dias úteis'],
      gradient: 'from-orange-600/20 to-amber-600/20',
      borderColor: 'border-orange-600/30'
    }
  ];

  const handleContinue = () => {
    if (!selectedMethod) return;
    setLoading(true);
    setTimeout(() => {
      onSelect(selectedMethod);
      setLoading(false);
    }, 500);
  };

  return (
    <div className="max-w-4xl mx-auto animate-fade-in">
      <button onClick={onBack} className="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors mb-8">
        <ChevronRight className="w-4 h-4 rotate-180" />
        Voltar
      </button>

      <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-8">
        <div className="text-center mb-10">
          <h2 className="text-4xl font-black mb-3">Escolha a forma de <span className="text-red-600">pagamento</span></h2>
          <p className="text-zinc-400 text-lg">Selecione o método que preferir para finalizar sua compra</p>
        </div>

        <div className="bg-gradient-to-r from-red-600/20 to-red-900/20 border border-red-600/30 rounded-2xl p-6 mb-10">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-zinc-400 mb-1">Valor total</p>
              <p className="text-3xl font-black text-red-600">R$ {amount}</p>
            </div>
            <div className="text-right">
              <p className="text-sm text-zinc-400 mb-1">Loja</p>
              <p className="text-xl font-bold">{storeData.storeName}</p>
            </div>
          </div>
        </div>

        <div className="grid md:grid-cols-3 gap-6 mb-10">
          {paymentMethods.map((method) => (
            <div
              key={method.id}
              onClick={() => setSelectedMethod(method.id)}
              className={`relative cursor-pointer rounded-2xl p-6 transition-all ${
                selectedMethod === method.id ? 'scale-105 shadow-2xl' : 'hover:scale-102'
              } bg-gradient-to-br ${method.gradient} border ${
                selectedMethod === method.id ? 'border-white/30' : method.borderColor
              }`}
            >
              <div className={`absolute -top-3 left-1/2 -translate-x-1/2 ${method.badgeColor} text-white text-xs font-black uppercase px-4 py-1.5 rounded-full shadow-lg`}>
                {method.badge}
              </div>

              {selectedMethod === method.id && (
                <div className="absolute -top-3 -right-3">
                  <div className="bg-white text-green-600 rounded-full p-1.5 shadow-lg">
                    <Check className="w-4 h-4" />
                  </div>
                </div>
              )}

              <div className={`w-16 h-16 rounded-2xl flex items-center justify-center mb-4 mx-auto ${
                selectedMethod === method.id ? 'bg-white/20' : 'bg-white/10'
              }`}>
                <div className={selectedMethod === method.id ? 'text-white' : 'text-zinc-300'}>
                  {method.icon}
                </div>
              </div>

              <div className="text-center mb-4">
                <h3 className="text-xl font-black mb-1">{method.name}</h3>
                <p className="text-sm text-zinc-400">{method.description}</p>
              </div>

              <div className="space-y-2">
                {method.features.map((feature, idx) => (
                  <div key={idx} className="flex items-start gap-2 text-xs text-zinc-300">
                    <div className="flex-shrink-0 w-4 h-4 rounded-full bg-white/10 flex items-center justify-center mt-0.5">
                      <Check className="w-2.5 h-2.5" />
                    </div>
                    <span>{feature}</span>
                  </div>
                ))}
              </div>

              <button className={`w-full mt-6 py-3 rounded-xl font-bold uppercase text-sm transition-all ${
                selectedMethod === method.id ? 'bg-white text-black' : 'bg-white/10 hover:bg-white/15 text-white'
              }`}>
                {selectedMethod === method.id ? 'Selecionado' : 'Selecionar'}
              </button>
            </div>
          ))}
        </div>

        <div className="bg-blue-600/10 border border-blue-600/20 rounded-xl p-6 mb-8">
          <div className="flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" />
            <div>
              <p className="font-bold mb-2 text-blue-400">Informações importantes:</p>
              <ul className="text-sm text-zinc-400 space-y-1">
                <li>• Pagamentos via PIX são aprovados instantaneamente</li>
                <li>• Cartões de crédito podem levar até 2 minutos para aprovação</li>
                <li>• Boletos podem levar até 2 dias úteis para compensação</li>
                <li>• Sua loja será ativada assim que o pagamento for confirmado</li>
              </ul>
            </div>
          </div>
        </div>

        <button
          onClick={handleContinue}
          disabled={!selectedMethod || loading}
          className="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed text-white py-5 rounded-xl font-black uppercase tracking-wider transition-all flex items-center justify-center gap-3 text-lg"
        >
          {loading ? (
            <>
              <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              Processando...
            </>
          ) : (
            <>
              Continuar para Pagamento
              <ChevronRight className="w-6 h-6" />
            </>
          )}
        </button>
      </div>
    </div>
  );
};

// ============= COMPONENTE: PaymentGateway =============
const PaymentGateway = ({ selectedPlan, storeData, paymentMethod, onBack }) => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [paymentData, setPaymentData] = useState(null);

  const createCheckout = async () => {
    setLoading(true);
    setError('');

    try {
      const token = localStorage.getItem('auth_token');
      
      const requestBody = {
        plan_id: selectedPlan.id,
        store_name: storeData.storeName,
        store_slug: storeData.storeSlug,
        customer_cpf: storeData.customerCpf,
        coupon_code: storeData.couponCode || '',
        payment_method: paymentMethod
      };

      const response = await fetch('/api/checkout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(requestBody)
      });

      const data = await response.json();

      if (response.ok && data.success) {
        setPaymentData(data);
        setLoading(false);
      } else {
        setError(data.error || 'Erro ao criar checkout');
        setLoading(false);
      }
    } catch (err) {
      console.error('Erro no checkout:', err);
      setError('Erro ao processar pagamento: ' + err.message);
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
          <p className="text-zinc-400">Gerando seu pagamento</p>
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
            <XCircle className="w-10 h-10 text-red-600" />
          </div>
          <h2 className="text-3xl font-black mb-4 text-red-600">Erro no Pagamento</h2>
          <p className="text-zinc-400 mb-8">{error}</p>
          <div className="flex gap-4 justify-center">
            <button onClick={onBack} className="px-8 py-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl font-bold transition-all">Voltar</button>
            <button onClick={createCheckout} className="px-8 py-3 bg-red-600 hover:bg-red-700 rounded-xl font-bold transition-all">Tentar Novamente</button>
          </div>
        </div>
      </div>
    );
  }

  if (paymentData) {
    if (paymentMethod === 'pix') {
      return <PixPaymentScreen paymentData={paymentData} onBack={onBack} />;
    } else {
      // Redirecionar para MercadoPago
      if (paymentData.init_point) {
        window.location.href = paymentData.init_point;
        return (
          <div className="max-w-2xl mx-auto animate-fade-in">
            <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-12 text-center">
              <div className="w-20 h-20 bg-green-600/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <CheckCircle className="w-10 h-10 text-green-600" />
              </div>
              <h2 className="text-3xl font-black mb-4">Redirecionando...</h2>
              <p className="text-zinc-400">Você será redirecionado para a página de pagamento</p>
            </div>
          </div>
        );
      }
    }
  }

  return null;
};

// ============= COMPONENTE: PixPaymentScreen =============
const PixPaymentScreen = ({ paymentData, onBack }) => {
  const [copied, setCopied] = useState(false);
  const [timeRemaining, setTimeRemaining] = useState(600);

  const copyToClipboard = () => {
    navigator.clipboard.writeText(paymentData.pix_code);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  return (
    <div className="max-w-2xl mx-auto animate-fade-in">
      <button onClick={onBack} className="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors mb-8">
        <ChevronRight className="w-4 h-4 rotate-180" />
        Voltar
      </button>

      <div className="bg-gradient-to-br from-white/[0.05] to-white/[0.02] border border-white/10 rounded-3xl p-8">
        <div className="bg-orange-600/10 border border-orange-600/20 rounded-xl p-4 mb-8 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Clock className="w-5 h-5 text-orange-600" />
            <span className="text-sm text-zinc-400">Tempo restante</span>
          </div>
          <span className="text-2xl font-black text-orange-600">{formatTime(timeRemaining)}</span>
        </div>

        <div className="text-center mb-8">
          <h2 className="text-3xl font-black mb-2">Pague com PIX</h2>
          <p className="text-zinc-400">Escaneie o QR Code ou copie o código PIX</p>
        </div>

        <div className="bg-red-600/10 border border-red-600/20 rounded-xl p-6 mb-8">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-zinc-400 mb-1">Valor</p>
              <p className="text-2xl font-black text-red-600">R$ {paymentData.amount}</p>
            </div>
            <div className="text-right">
              <p className="text-sm text-zinc-400 mb-1">Loja</p>
              <p className="font-black">{paymentData.store_name}</p>
            </div>
          </div>
        </div>

        {paymentData.qr_code_base64 && (
          <div className="bg-white rounded-2xl p-8 mb-8">
            <div className="flex justify-center">
              <img src={paymentData.qr_code_base64} alt="QR Code PIX" className="w-64 h-64" />
            </div>
          </div>
        )}

        <div className="bg-black/50 rounded-xl p-6 mb-8">
          <label className="block text-xs font-bold uppercase tracking-wider text-zinc-400 mb-3">Código PIX Copia e Cola</label>
          <div className="flex gap-3">
            <div className="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 overflow-hidden">
              <p className="text-sm font-mono truncate text-zinc-300">{paymentData.pix_code}</p>
            </div>
            <button onClick={copyToClipboard} className="px-6 bg-red-600 hover:bg-red-700 rounded-xl font-bold transition-all flex items-center gap-2 whitespace-nowrap">
              {copied ? (
                <>
                  <Check className="w-5 h-5" />
                  Copiado!
                </>
              ) : (
                <>
                  <Copy className="w-5 h-5" />
                  Copiar
                </>
              )}
            </button>
          </div>
        </div>

        <div className="bg-green-600/10 border border-green-600/20 rounded-xl p-6">
          <p className="text-sm text-green-400 text-center">✅ Após o pagamento, sua loja será ativada automaticamente!</p>
        </div>
      </div>
    </div>
  );
};

// ============= COMPONENTE PRINCIPAL: App =============
const App = () => {
  const [step, setStep] = useState('plans');
  const [selectedPlan, setSelectedPlan] = useState(null);
  const [storeData, setStoreData] = useState(null);
  const [paymentMethod, setPaymentMethod] = useState(null);

  const plans = [
    {
      id: 'starter',
      name: 'STARTER',
      description: 'Perfeito para começar',
      price: '14,99',
      price_numeric: 14.99,
      icon: <Zap className="w-6 h-6 text-red-600" />,
      features: ['1 Servidor Minecraft', 'Checkout Responsivo', 'Suporte via Ticket', 'Plugin de Entrega']
    },
    {
      id: 'enterprise',
      name: 'ENTERPRISE',
      description: 'Para redes sérias',
      price: '25,99',
      price_numeric: 25.99,
      icon: <Shield className="w-6 h-6 text-red-600" />,
      features: ['5 Servidores', 'Checkout Customizável', 'Suporte Prioritário 24/7', 'Analytics Avançado'],
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
      features: ['Servidores Ilimitados', 'Whitelabel Completo', 'Gerente de Contas', 'Integrações Custom']
    }
  ];

  return (
    <div className="min-h-screen bg-black text-white p-4">
      <div id="particles-js" className="fixed inset-0 z-0"></div>
      <div className="fixed inset-0 bg-[radial-gradient(circle_at_center,_rgba(220,38,38,0.1)_0%,_transparent_70%)] z-0"></div>

      <div className="relative z-10 max-w-7xl mx-auto py-12">
        <div className="text-center mb-16">
          <div className="inline-flex items-center gap-3 mb-6">
            <div className="w-12 h-12 bg-gradient-to-br from-red-600 to-red-900 rounded-xl flex items-center justify-center font-black">S</div>
            <span className="text-2xl font-black tracking-tighter uppercase">Split<span className="text-red-600">Store</span></span>
          </div>
          
          {step === 'plans' && (
            <>
              <h1 className="text-5xl font-black mb-4">Escolha seu <span className="text-red-600">Plano</span></h1>
              <p className="text-zinc-400 text-lg">Comece agora e transforme suas vendas</p>
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

        {step === 'payment_method' && selectedPlan && storeData && (
          <PaymentMethodSelector
            amount={storeData.total}
            storeData={storeData}
            onSelect={(method) => {
              setPaymentMethod(method);
              setStep('checkout');
            }}
            onBack={() => setStep('setup')}
          />
        )}

        {step === 'checkout' && selectedPlan && storeData && paymentMethod && (
          <PaymentGateway
            selectedPlan={selectedPlan}
            storeData={storeData}
            paymentMethod={paymentMethod}
            onBack={() => setStep('payment_method')}
          />
        )}

        <div className="text-center mt-16">
          <p className="text-zinc-700 text-xs">© 2026 SplitStore • Todos os direitos reservados</p>
        </div>
      </div>
    </div>
  );
};

export default App