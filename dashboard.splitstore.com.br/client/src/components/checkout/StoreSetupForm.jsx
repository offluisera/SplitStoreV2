// src/components/checkout/StoreSetupForm.jsx
import React, { useState } from 'react';
import { ChevronRight, Check, X, AlertCircle } from 'lucide-react';
import { checkoutAPI, storeAPI } from '../../services/api';

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

    // Auto-generate slug from store name
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

    if (name === 'storeSlug') {
      setSlugError('');
    }
  };

  const checkSlugAvailability = async () => {
    if (!formData.storeSlug) return;
    
    try {
      const response = await storeAPI.checkSlug(formData.storeSlug);
      if (!response.data.available) {
        setSlugError('Este slug já está em uso');
      }
    } catch (error) {
      console.error('Erro ao verificar slug:', error);
    }
  };

  const handleApplyCoupon = async () => {
    if (!formData.couponCode) return;
    
    setLoading(true);
    setCouponError('');
    
    try {
      const response = await checkoutAPI.validateCoupon(formData.couponCode);
      
      if (response.data.valid) {
        setCouponApplied({
          code: formData.couponCode,
          discount: response.data.discount_value,
          type: response.data.discount_type
        });
      } else {
        setCouponError('Cupom inválido ou expirado');
      }
    } catch (error) {
      setCouponError(error.response?.data?.error || 'Erro ao validar cupom');
    } finally {
      setLoading(false);
    }
  };

  const calculateTotal = () => {
    let total = parseFloat(selectedPlan.price.replace(',', '.'));
    
    if (couponApplied) {
      if (couponApplied.type === 'percentage') {
        total = total * (1 - couponApplied.discount / 100);
      } else {
        total = total - couponApplied.discount;
      }
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
            Preencha os dados para criar sua conta no plano {selectedPlan.name}
          </p>
        </div>

        {/* Plan Summary */}
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

        {/* Store Info Form */}
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
                onBlur={checkSlugAvailability}
                className={`flex-1 bg-white/5 border ${slugError ? 'border-red-500' : 'border-white/10'} rounded-xl px-4 py-3.5 focus:outline-none focus:border-red-600/50 transition-colors`}
                placeholder="minha-loja"
                required
              />
              <span className="text-zinc-500 text-sm">.splitstore.com.br</span>
            </div>
            {slugError && (
              <div className="mt-2 flex items-center gap-2 text-red-500 text-sm">
                <AlertCircle className="w-4 h-4" />
                {slugError}
              </div>
            )}
          </div>

          {/* Coupon Code */}
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
                  className="px-6 bg-red-600 hover:bg-red-700 disabled:bg-red-800 disabled:opacity-50 rounded-xl font-bold transition-all"
                >
                  {loading ? 'Validando...' : 'Aplicar'}
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
                Cupom "{couponApplied.code}" aplicado! {couponApplied.discount}% de desconto
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

        {/* Total */}
        <div className="bg-gradient-to-r from-red-600/20 to-red-900/20 border border-red-600/30 rounded-xl p-6 mb-8">
          <div className="flex items-center justify-between mb-4">
            <span className="text-zinc-400">Subtotal</span>
            <span className="font-semibold">R$ {selectedPlan.price}</span>
          </div>
          {couponApplied && (
            <div className="flex items-center justify-between mb-4 text-green-500">
              <span>Desconto ({couponApplied.discount}{couponApplied.type === 'percentage' ? '%' : ' R$'})</span>
              <span className="font-semibold">
                - R$ {couponApplied.type === 'percentage' 
                  ? (parseFloat(selectedPlan.price.replace(',', '.')) * couponApplied.discount / 100).toFixed(2).replace('.', ',')
                  : couponApplied.discount.toFixed(2).replace('.', ',')}
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

        {/* Action Buttons */}
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

export default StoreSetupForm;