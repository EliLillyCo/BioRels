#ifndef  INTER_CARBONYLPI_H
#define INTER_CARBONYLPI_H
#include <math.h>
#include "headers/molecule/mmring.h"
#include "headers/molecule/mmring_utils.h"

namespace protspace
{
    class InterData;
    class InterCarbonylPI
    {
    protected:
        /**
         * \brief Maximum distance allowed between the ring center and the Oxygen
         *
         * This is based on dx.doi.org/10.1021/jp0727421 Figure 2.
         * The optimal distance is around 3.3 Angstroems. and goes up to 7 Angstroems.
         * The generic cut-off will be defined at 5 Angstroems.
         */
        static double mTD_ArC_Ox;
        /**
         * \brief Maximum distance allowed between the ring center and the Carbon
         *
         * Based on a generic cut-off at 5 Angstroems for Ox-Center distance,
         * we add a generic cut-off at 6 Angstroems for C-Center distance
         *
         */
        static double mTD_ArC_C;

        /**
         * \brief Distance of the Projected Oxygen/Carbon on the ring from the ring center
         *
         * Either the Carbon or the Oxygen must lie within the ring circle with some
         * error range. The average distance in a benzene between ring atom and ring center
         * is around 1.4 Angstroems. So we put a threshold at 1.6.
         */
        static double mTD_Shift;


        /**
         * \brief Optimal dihedral angle between the carbonyl plane and the ring plane
         *
         * Based on figure 2, the optimal dihedral angle between the carbonyl plane and the ring
         * plane is 0 deg
         */
        static double mCD_carbonyl_ring;

        /**
         * \brief Range around optimal dihedral angle between the carbonyl plan and the ring plane
         */
        static double mRD_carbonyl_ring;

        static std::string mDebugData;
        static bool mDebugSave;

        const MMRing& mRing;
        MMAtom*  mCarbon;
        MMAtom* mOxygen;
        ring_atm_inf mDataOx;
        ring_atm_inf mDataCa;
        Coords mVectCarbonyl;
        double mCD_Value;
    public:
        InterCarbonylPI(const MMRing& pRing);
        bool setOxygen(MMAtom& atom);
        void clear();

        /**
         * @brief runMath
         * @throw 310601 MMAtom::getAtomNotAtom No alternative atom found
         */
        void runMath();
        bool isInteracting(InterData &data)const;
        void saveToMole()const;
        void exportDebugData()const;
        const double& getDistCenterRingToAtom()const {return mDataOx.mRc;}
        const double& getAngle()const {return mDataOx.mTheta;}
    };
}

#endif //INTER_CARBONYLPI_H
