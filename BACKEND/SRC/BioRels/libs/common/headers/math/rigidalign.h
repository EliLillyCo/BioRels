#ifndef RIGIDALIGN_H
#define RIGIDALIGN_H

#include "headers/statics/grouplist.h"
#include "headers/math/coords.h"
#include "headers/statics/objectpool.h"
namespace protspace
{

typedef       std::vector<Coords>       CoordList;
class RigidAlign
{
    friend class RigidBody;
public:
    static ObjectPool<double>& sPoolDbl;
    static ObjectPool<Coords>& sPoolCoo;
private:
    std::vector<size_t> mRotMatPos;
protected:
    /**
     * @brief Rotation Matrix (vector of 9 values)
     */
    GroupList<double>  RotMat;

    /**
     * @brief Geometric center of reference set of coordinates
     */
    Coords TransRigid;

    /**
     * @brief Geometric center of mobile set of coordinates
     */
    Coords TransMob;

    double rmsd;

public:
    RigidAlign();
    ~RigidAlign();
    RigidAlign(const RigidAlign& obj);
    RigidAlign& operator=(const RigidAlign& obj);
    Coords& getTransMob(){return TransMob;}
    const Coords& getTransMob()const{return TransMob;}
    Coords& getTransRigid(){return TransRigid;}
    const Coords& getTransRigid()const{return TransRigid;}
    const GroupList<double>& getMatrix()const {return RotMat;}
    void setMatrix(const size_t&pos, const double& value){RotMat.get(pos)=value;}
    /**
     * @brief Rotate the given mobile list into a reference list
     * @param list List of coordinates to change
     */
    void mobilToRef           ( CoordList& list) const;



    /**
     * @brief Rotate the given mobile list into a reference list
     * @param list List of coordinates to change
     */
    void mobilToRef           ( std::vector<Coords*>& list) const;


    /**
     * @brief Rotate the given reference list into a mobilelist
     * @param list List of coordinate to rotate
     */
    void refToMobil           ( CoordList& list)const;

    void clear();
    const double& getRMSD()const{return rmsd;}

    void refToMobil(std::vector<Coords *> &liste) const;
};
}
#endif // RIGIDALIGN_H

